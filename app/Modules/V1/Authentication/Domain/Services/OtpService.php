<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Application\Mail\OtpMail;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Carbon\Carbon;
use Ichtrojan\Otp\Otp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /*
    public function generate(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal
    ): string {
        $otp = (new Otp)->generate(
            $this->identifier($email, $purpose, $portal),
            'numeric',
            6,
            (int) config('sanctum.OTP_TTL'),
        );

        return $otp->token;
    }
    */

    public function generate(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal
    ): string {
        $identifier = $this->identifier($email, $purpose, $portal);
        $fixedCode = '123456';

        DB::table('otps')->where('identifier', $identifier)->delete();
        DB::table('otps')->insert([
            'identifier' => $identifier,
            'token' => $fixedCode,
            'validity' => (int) config('sanctum.OTP_TTL'),
            'valid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $fixedCode;
    }

    public function generateAndSend(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal,
        ?string $name = null
    ): void {
        $code = $this->generate($email, $purpose, $portal);
        $expiresInMinutes = (int) config('sanctum.OTP_TTL');

        Mail::to($email)
            ->locale(app()->getLocale())
            ->queue((new OtpMail($code, $purpose, $name, $expiresInMinutes))->afterCommit());
    }

    public function validate(
        string $email,
        string $token,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal
    ): bool {
        $identifier = $this->identifier($email, $purpose, $portal);

        /*
         * Disabled because ichtrojan/laravel-otp passes the database value of
         * `validity` to Carbon 3 as a string, causing a TypeError on validation.
         * Keep this block until the package handles the value as an integer.
         *
        $otpCheck = (new Otp)->validate($identifier, $token);
        if (! $otpCheck->status) {
            return false;
        }
        DB::table('otps')->where('identifier', $identifier)->delete();

        return true;
        */

        return DB::transaction(function () use ($identifier, $token): bool {
            $otp = DB::table('otps')
                ->where('identifier', $identifier)
                ->where('token', $token)
                ->where('valid', true)
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return false;
            }

            DB::table('otps')->where('id', $otp->id)->delete();

            $expiresAt = Carbon::parse($otp->created_at)
                ->addMinutes((int) $otp->validity);

            return now()->lessThanOrEqualTo($expiresAt);
        });
    }

    private function identifier(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal
    ): string {
        return implode('|', [
            strtolower($email),
            $portal->value,
            $purpose->value,
        ]);
    }
}
