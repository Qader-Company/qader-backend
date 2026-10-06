# 08 — Currency and Salary Conversion

## Goal

When a Talent enters current or expected salary, Qader stores the original amount and the selected currency. The application can then display an equivalent amount in any other supported currency using the latest available exchange rate.

The original salary must never be overwritten by a converted value.

## Supported Currencies

The initial supported salary currencies are:

| Code | Currency | Region |
| --- | --- | --- |
| `EGP` | Egyptian Pound | Egypt |
| `USD` | United States Dollar | International |
| `EUR` | Euro | Europe |
| `SAR` | Saudi Riyal | Saudi Arabia |
| `AED` | UAE Dirham | United Arab Emirates |
| `QAR` | Qatari Riyal | Qatar |
| `KWD` | Kuwaiti Dinar | Kuwait |
| `BHD` | Bahraini Dinar | Bahrain |
| `OMR` | Omani Rial | Oman |

The existing `currencies` table remains the source of truth. Only currencies with `is_active = true` are selectable. A future admin setting can enable or disable a supported currency without changing Talent salary records already stored in that currency.

## Salary Storage

Keep the original amount and currency together:

```text
talents.current_salary            decimal(12,2), nullable
talents.current_currency_id       foreign key, nullable
talents.expected_salary_min       decimal(12,2), nullable
talents.expected_salary_max       decimal(12,2), nullable
talents.expected_currency_id      foreign key, nullable
```

Rules:

- An amount without its currency is invalid.
- Minimum expected salary cannot exceed maximum expected salary.
- A current salary and expected salary may use different currencies.
- These values represent the Talent's submitted values and are never replaced by converted amounts.

## Exchange-Rate Model

When salary conversion becomes part of Stage 4, add a focused `currency_exchange_rates` table.

| Field | Meaning |
| --- | --- |
| `base_currency_id` | The platform base currency. Initial recommendation: `EGP`. |
| `currency_id` | The currency being measured against the base currency. |
| `rate_to_base` | How many base-currency units equal one unit of `currency_id`. |
| `source` | Where the rate came from: `manual` initially, provider name later. |
| `effective_at` | When the rate became valid. |
| `fetched_at` | When Qader stored the rate. |

There is one active/latest rate per supported currency. The base currency has a rate of `1.00000000`.

## Conversion Formula

With EGP as the initial base currency:

```text
amount_in_target = amount_in_source × source.rate_to_base ÷ target.rate_to_base
```

Example: conversion from USD to EUR uses the latest USD-to-EGP rate and EUR-to-EGP rate. This avoids storing every possible currency pair.

## API Behavior

The API returns original values by default:

```json
{
  "expected_salary": {
    "min": "3000.00",
    "max": "4500.00",
    "currency": "USD"
  }
}
```

When the caller requests a supported target currency, the API may add a calculated display value and the rate timestamp:

```json
{
  "expected_salary": {
    "min": "3000.00",
    "max": "4500.00",
    "currency": "USD",
    "converted": {
      "currency": "EGP",
      "min": "150000.00",
      "max": "225000.00",
      "rate_effective_at": "2026-08-25T00:00:00Z"
    }
  }
}
```

Converted values are display values only. They are calculated at response time from the latest rate and must be labeled with the target currency and rate timestamp.

## Delivery Plan

1. Stage 1–3: keep salary and currency out of scope.
2. Stage 4: activate salary fields, seed supported currencies, and add exchange-rate storage.
3. Stage 4: provide salary conversion in Talent and admin responses.
4. Later: integrate an exchange-rate provider behind an Infrastructure service; manual rates remain a safe fallback.

Do not add an external exchange-rate provider or a background job until Stage 4 starts and the update frequency is agreed.
