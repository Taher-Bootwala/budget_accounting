# Vendor Portal & PO Approval Workflow Implementation Plan

## Goal
Enable a Vendor Portal where vendors can:
1.  Manage their own products.
2.  Review and Approve Purchase Orders (PO) sent by the Admin.
3.  Restrict Admin's PO creation to only show products belonging to the selected vendor.

## Proposed Changes

### Database Changes
#### [MODIFY] Database Schema
- **Table**: `products`
    - Add `vendor_id` (INT, Nullable, Foreign Key to `contacts.id`).
- **Table**: `documents`
    - Add 'pending_vendor' to the status ENUM.

### Admin Panel
#### [MODIFY] `views/documents/create.php`
- JavaScript to fetch products via AJAX when Vendor is selected.
#### [NEW] `api/get_products_by_vendor.php`
- JSON API to return products for a specific vendor.

### Vendor Portal
#### [NEW] `portal/my_products.php`
- Interface for Vendors to add/list products.
#### [MODIFY] `portal/view_document.php`
- Add "Approve" button for Vendors on POs.
