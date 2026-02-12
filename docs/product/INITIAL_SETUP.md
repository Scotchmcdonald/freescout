# Getting Started: Initial System Setup

This guide walks you through the initial configuration of your environment, focusing on setting up your client base and service catalog.

## 1. Setting Up Clients (CRM)

The CRM module allows you to manage the organizations and individuals you support.

### Manual Creation
To add a new client manually:
1. Navigate to **Clients** in the main navigation menu (or **CRM > Clients**).
2. Click the **New Client** button (or `/crm/clients/create`).
3. Fill in the required details:
   - **Name**: The client or organization name.
   - **Email**: Primary contact email.
   - **Status**: Set to 'Active' for current clients.
   - **Tier**: (Optional) Service tier (e.g., Gold, Silver).
4. Click **Save** to create the client record.

### Bulk Import (Templates)
While a direct CSV import feature is currently under development, we have provided standard CSV templates you can use to prepare your data.

- [Download Clients Import Template](../../public/import_templates/clients_import_template.csv)
- [Download Companies Import Template](../../public/import_templates/companies_import_template.csv)

You can import these files in the **Settings > Data Import** section.

## 2. Setting Up Software Products

The Software Subscriptions module allows you to define the products and services you sell or manage for your clients.

### Manual Creation
To add a new product to your catalog:
1. Navigate to **Software Subscriptions** > **Products**.
2. Click **Create Product** (or `/software-subscriptions/products/create`).
3. Enter the product details:
   - **Name**: Product name (e.g., "Microsoft 365 Business Standard").
   - **Vendor**: The software vendor (e.g., "Microsoft").
   - **Pricing Type**: Select 'Flat Rate' or 'Tiered'.
   - **Cost & Price**: Enter your vendor cost and the default selling price.
4. Configure additional settings like **Licensing Model** (User/Device) and **Billing Frequency**.
5. Click **Create** to save the product.

### Bulk Import (Templates)
Prepare your product catalog using the standardized template below.

- [Download Products Import Template](../../public/import_templates/products_import_template.csv)

You can import this file in the **Settings > Data Import** section.

## 3. Next Steps

Once your clients and products are set up, you can start:
- **Assigning Subscriptions**: Link products to clients via the Software Subscriptions module.
- **Managing Assets**: Use the Asset Management module to track client devices.
- **Configuring Billing**: Set up billing templates in the PIB module.
