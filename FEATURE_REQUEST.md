# Feature Request: API Keys

## Summary
This issue proposes the implementation of API keys in the lljaworski/api repository.

## Details
1. **Admin-Only Generation and Use:** Only admin users should have the ability to generate and use API keys.
2. **User-Specific API Keys:** Each API key must be associated with a specific user.
3. **Defined API Roles:** All endpoints that require API keys should check for a defined API role for access.
4. **Exclusive Management Rights:** Admins should have exclusive rights over the management of API keys.

## Benefits
- Enhanced security by limiting API key generation and usage to admin users.
- Improved accountability through user-specific API keys.
- Better role management and access control for API usage.