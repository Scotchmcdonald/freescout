# Wave 15 - Phase 2 Implementation

Date: 2026-03-24
Status: Not started

## Planned Work
1. Add unit tests for Payment helper methods:
   - isSuccessful, isFailed, isPending, isRefunded
   - canBeRefunded (window/dispute/amount constraints)
   - getRemainingRefundableAmount
   - getFormattedAmount, getFormattedTotalAmount
2. Add unit tests for PaymentMethod helper methods:
   - isExpired, isValid, isNearExpiration
   - getMaskedCardNumber, getDisplayName
3. Add unit tests for Role/Channel helper methods:
   - Role::isSuperAdmin, isInternal, isClient
   - Channel::isActive

## Risks
- Datetime cast behavior in pure Eloquent doubles (created_at/expires_at).
- Avoid triggering save/update hooks in Payment and PaymentMethod.
