---
title: Company Entity Management CRUD Specification
version: 1.0
date_created: 2025-10-15
last_updated: 2025-10-15
owner: API Platform Development Team
tags: [entity, crud, company, api, business]
---

# Introduction

This specification defines the requirements for implementing a comprehensive Company entity with full CRUD operations in the API Platform project. The Company entity is designed to support Polish tax and business requirements, including KSeF (Krajowy System e-Faktur) compliance, VAT handling, and international business operations.

## 1. Purpose & Scope

This specification provides detailed requirements for implementing a Company entity that supports:
- Complete CRUD operations via API Platform endpoints
- Polish tax system compliance (NIP, KSeF integration)
- EU VAT identification and EORI numbers
- Multi-address support (primary and correspondence addresses)
- Contact information management
- Advanced taxpayer status and role management

The specification is intended for developers implementing the Company entity within the existing Symfony 7.3 + API Platform 4.2 architecture.

## 2. Definitions

- **KSeF**: Krajowy System e-Faktur (National e-Invoice System) - Polish electronic invoicing system
- **NIP**: Numer Identyfikacji Podatkowej - Polish tax identification number
- **EORI**: Economic Operators Registration and Identification number for EU customs
- **VAT**: Value Added Tax
- **GLN**: Global Location Number - GS1 standard for location identification
- **JST**: Jednostka Samorządu Terytorialnego - Local Government Unit
- **GV**: Grupa VAT - VAT Group

## 3. Requirements, Constraints & Guidelines

### Core Requirements

- **REQ-001**: Company entity MUST implement all identification data fields as specified
- **REQ-002**: Company entity MUST support both primary and correspondence addresses
- **REQ-003**: Company entity MUST provide contact information fields (email, phone)
- **REQ-004**: Company entity MUST include additional information fields for tax compliance
- **REQ-005**: All CRUD operations MUST be exposed via API Platform endpoints
- **REQ-006**: Company entity MUST follow CQRS architecture patterns
- **REQ-007**: Company entity MUST implement soft delete functionality
- **REQ-008**: All string fields MUST have appropriate length constraints

### Security Requirements

- **SEC-001**: Company management endpoints MUST require ROLE_ADMIN access
- **SEC-002**: All input data MUST be validated and sanitized
- **SEC-003**: Sensitive tax information MUST be handled securely

### Validation Constraints

- **CON-001**: tax_id (NIP) MUST follow Polish NIP format when provided
- **CON-002**: email field MUST validate email format when provided
- **CON-003**: country_code fields MUST use ISO 3166-1 alpha-2 format
- **CON-004**: taxpayer_prefix MUST be valid EU country code when provided
- **CON-005**: phone_number MUST accept international format
- **CON-006**: share_percentage MUST be between 0 and 100 when provided

## 4. API Endpoints

| Method | Endpoint | Description | Security |
|--------|----------|-------------|----------|
| GET | /api/companies | List all companies (paginated) | ROLE_ADMIN |
| GET | /api/companies/{id} | Get single company | ROLE_ADMIN |
| POST | /api/companies | Create new company | ROLE_ADMIN |
| PUT | /api/companies/{id} | Update company (full) | ROLE_ADMIN |
| PATCH | /api/companies/{id} | Update company (partial) | ROLE_ADMIN |
| DELETE | /api/companies/{id} | Soft delete company | ROLE_ADMIN |

## 5. Acceptance Criteria

- **AC-001**: Given valid company data, When creating a company via POST /api/companies, Then the company is created and returns 201 status
- **AC-002**: Given an existing company ID, When retrieving via GET /api/companies/{id}, Then the company data is returned with 200 status
- **AC-003**: Given valid update data, When updating via PATCH /api/companies/{id}, Then only provided fields are updated
- **AC-004**: Given an existing company ID, When deleting via DELETE /api/companies/{id}, Then the company is soft deleted (deletedAt set)
- **AC-005**: Given invalid tax ID format, When creating/updating a company, Then validation error 422 is returned
- **AC-006**: Given unauthenticated request, When accessing any company endpoint, Then 401 Unauthorized is returned
- **AC-007**: Given non-admin user, When accessing any company endpoint, Then 403 Forbidden is returned
- **AC-008**: Given pagination parameters, When listing companies via GET /api/companies, Then paginated results are returned

## 6. Field Specifications

### Identification Data Fields
- `tax_id` - Polish NIP (max 20 chars, nullable)
- `name` - Company name (max 255 chars, required)
- `taxpayer_prefix` - VAT EU prefix (max 4 chars, nullable)
- `eori_number` - EORI number (max 17 chars, nullable)
- `eu_country_code` - EU country code (max 4 chars, nullable)
- `vat_reg_number_eu` - EU VAT number (max 20 chars, nullable)
- `other_id_country_code` - Other ID country (max 4 chars, nullable)
- `other_id_number` - Other ID number (max 50 chars, nullable)
- `no_id_marker` - No ID marker (boolean, nullable)
- `client_number` - Client number (max 50 chars, nullable)

### Address Fields
- `country_code` - Country code (max 4 chars, nullable)
- `address_line_1` - Address line 1 (max 255 chars, nullable)
- `address_line_2` - Address line 2 (max 255 chars, nullable)
- `gln` - Global Location Number (max 13 chars, nullable)

### Correspondence Address Fields
- `correspondence_country_code` - Correspondence country (max 4 chars, nullable)
- `correspondence_address_line_1` - Correspondence address 1 (max 255 chars, nullable)
- `correspondence_address_line_2` - Correspondence address 2 (max 255 chars, nullable)
- `correspondence_gln` - Correspondence GLN (max 13 chars, nullable)

### Contact Details
- `email` - Email address (max 255 chars, nullable, email format)
- `phone_number` - Phone number (max 20 chars, nullable)

### Additional Information
- `taxpayer_status` - Status (1-4: liquidation, restructuring, bankruptcy, succession, nullable)
- `jst_marker` - JST marker (1-2, nullable)
- `gv_marker` - VAT group marker (1-2, nullable)
- `role` - Third party role (1,2,4,6,11, nullable)
- `other_role_marker` - Other role marker (boolean, nullable)
- `role_description` - Role description (max 255 chars, nullable)
- `share_percentage` - Share percentage (0-100, decimal, nullable)

## 7. Related Specifications

- [CQRS Architecture Instructions](/.github/instructions/cqrs.instructions.md)
- [API Platform Instructions](/.github/instructions/api-platform.instructions.md)
- [Database Instructions](/.github/instructions/database.instructions.md)
- [Security Instructions](/.github/instructions/security.instructions.md)
- [Test Instructions](/.github/instructions/test.instructions.md)