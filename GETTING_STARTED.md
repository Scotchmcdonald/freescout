# Getting Started Guide

This guide will walk you through the essential steps to set up your environment, from creating a company to assigning assets and configuring service plans.

## 1. Creating a Company (Client)

The first step is to establish the customer organization in the system. In this application, companies are referred to as **Clients** within the CRM module.

**Steps:**
1.  Navigate to the **CRM** module in the main menu.
2.  Click on **Clients**.
3.  Click the **Create New Client** button.
4.  Fill in the required information:
    *   **Name**: The legal name of the company.
    *   **Email**: Primary contact email for the company (optional).
    *   **Status**: Set to `Active`.
    *   **Phone/Address**: Additional contact details (optional).
5.  Click **Save** or **Create Client**.

*Under the hood, this creates a `Modules\Crm\Models\Client` record, which serves as the parent entity for users, assets, and contracts.*

---

## 2. Creating Products

Before you can assign services to a company, you need to define the products involved (e.g., "Google Workspace Seat", "Managed Antivirus", "Hourly Support").

**Steps:**
1.  Navigate to the **Billing** or **Product Inventory** (PIB) section.
2.  Select **Products**.
3.  Click **Create Product**.
4.  Enter the product details:
    *   **Name**: A descriptive name (e.g., "Gold Support Plan").
    *   **Code**: A unique identifier for the product (e.g., `SUP-GOLD`).
    *   **Status**: Set to `Active`.
5.  Click **Save**.

*This creates a `Modules\PIB\Models\Product` record available for assignment to any client.*

---

## 3. Assigning Users to a Company

Once a company (Client) exists, you can add people to it. These are referred to as **Contacts**.

**Steps:**
1.  Navigate to **CRM > Clients**.
2.  Click on the name of the client you just created to view their details.
3.  Locate the **Contacts** tab or section.
4.  Click **Add Contact**.
5.  Fill in the user's details:
    *   **First Name**: e.g., "John".
    *   **Last Name**: e.g., "Doe".
    *   **Email**: Their business email address (Required).
    *   **Role**: e.g., "Manager", "Admin", "Billing Contact".
    *   **Primary Contact**: Check this box if they are the main point of contact.
6.  Click **Save**.

*This creates a `Modules\Crm\Models\Contact` record linked to the Client.*

---

## 4. Assigning Assets

You can track physical or digital assets (like laptops, servers, or software licenses) assigned to a company.

**Steps:**
1.  Navigate to **Assets** or **Asset Management**.
2.  Click **Inventory** `->` **Add Asset**.
3.  Fill in the asset details:
    *   **Serial Number**: The unique hardware ID or license key.
    *   **Asset Type**: e.g., "Laptop", "Desktop", "License".
    *   **Status**: e.g., "Active", "In Stock", "Deployed".
    *   **Client**: Select the Company/Client you created in Step 1.
    *   **Model**: (Optional) specific model information.
4.  Click **Save**.

*Note: The asset is now linked to the client and can be further assigned to a specific user (contact) if needed.*

---

## 5. Setting up a Service Plan (Entitlements)

Finally, to bill the client for products or services, you assign **Entitlements**. This defines what the client has purchased, the quantity, and the billing cycle.

**Steps:**
1.  Navigate to **Billing** or search for the **Client** in the CRM.
2.  Go to the **Entitlements** or **Services** section for that client.
3.  Click **Provision Entitlement** (or "Add Service").
4.  Configure the service plan:
    *   **Product**: Select the Product you created in Step 2.
    *   **Quantity**: The number of units (e.g., 50 seats).
    *   **Rate**: The price per unit (e.g., $10.00).
    *   **Billing Cycle**: Select `Monthly`, `Quarterly`, or `Annually`.
5.  Click **Provision** or **Save**.

*This creates a `Modules\PIB\Models\Entitlement` record, which the billing system uses to generate invoices.*
