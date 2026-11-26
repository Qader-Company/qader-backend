<?php

namespace App\Modules\V1\Authentication\Domain\Services;

use App\Modules\V1\Authentication\Application\Mail\OtpMail;
use App\Modules\V1\Authentication\Domain\ValueObjects\OtpPurposeEnum;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generate(
        string $email,
        OtpPurposeEnum $purpose,
        PortalTypeEnum $portal
    ): string {
        $identifier = $this->identifier($email, $purpose, $portal);
        $code = app()->environment(['local', 'testing'])
            ? '123456'
            : str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('otps')->where('identifier', $identifier)->delete();
        DB::table('otps')->insert([
            'identifier' => $identifier,
            'token' => $code,
            'validity' => (int) config('sanctum.OTP_TTL'),
            'valid' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $code;
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
