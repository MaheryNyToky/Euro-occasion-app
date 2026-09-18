<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tenant
        $tenant = Tenant::firstOrCreate(
            ['code' => 'eurocasion'],
            [
                'name' => 'Eurocasion Madagascar',
                'accounting_currency' => 'MGA',
                'timezone' => 'Indian/Antananarivo',
                'status' => 'active',
            ]
        );

        TenantContext::setTenantId($tenant->id);

        // 2. Permissions (Global)
        $permissionDefinitions = [
            ['code' => 'admin', 'name' => 'Accès Administrateur Global', 'category' => 'system'],
            ['code' => 'catalog:read', 'name' => 'Consulter le catalogue', 'category' => 'catalog'],
            ['code' => 'catalog:write', 'name' => 'Modifier le catalogue', 'category' => 'catalog'],
            ['code' => 'stock:read', 'name' => 'Consulter les stocks', 'category' => 'stock'],
            ['code' => 'stock:write', 'name' => 'Mouvements de stock', 'category' => 'stock'],
            ['code' => 'sales:read', 'name' => 'Consulter les ventes', 'category' => 'sales'],
            ['code' => 'sales:write', 'name' => 'Créer des ventes', 'category' => 'sales'],
            ['code' => 'purchases:read', 'name' => 'Consulter les achats', 'category' => 'purchases'],
            ['code' => 'purchases:write', 'name' => 'Gérer les achats', 'category' => 'purchases'],
            ['code' => 'reports:read', 'name' => 'Consulter les rapports', 'category' => 'reports'],
        ];

        $allPermissionIds = [];
        foreach ($permissionDefinitions as $pDef) {
            $perm = Permission::firstOrCreate(['code' => $pDef['code']], $pDef);
            $allPermissionIds[] = $perm->id;
        }

        // 3. Admin Role
        $adminRole = Role::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'admin'],
            [
                'name' => 'Administrateur',
                'description' => 'Accès complet au système et à tous les modules',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync($allPermissionIds);

        // 4. Admin User
        $adminUser = User::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@eurocasion.mg'],
            [
                'name' => 'Admin Eurocasion',
                'password' => Hash::make('SecretPass123!'),
                'status' => 'active',
            ]
        );

        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }

        // 5. Units of measure
        $unitPce = Unit::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'PCE'],
            ['name' => 'Pièce', 'precision' => 0, 'is_base' => true, 'is_active' => true]
        );

        $unitKg = Unit::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'KG'],
            ['name' => 'Kilogramme', 'precision' => 3, 'is_base' => true, 'is_active' => true]
        );

        $unitLitre = Unit::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'L'],
            ['name' => 'Litre', 'precision' => 2, 'is_base' => true, 'is_active' => true]
        );

        $unitCarton = Unit::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CRT'],
            ['name' => 'Carton (10 pcs)', 'precision' => 0, 'is_base' => false, 'is_active' => true]
        );

        UnitConversion::firstOrCreate(
            ['tenant_id' => $tenant->id, 'from_unit_id' => $unitCarton->id, 'to_unit_id' => $unitPce->id],
            ['factor' => 10, 'is_active' => true]
        );

        // 6. Categories
        $catElec = Category::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ELEC'],
            ['name' => 'Électronique & Multimédia', 'description' => 'Produits high-tech et multimédia', 'is_active' => true]
        );

        $catTel = Category::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'TEL'],
            ['name' => 'Téléphonie & Smartphones', 'parent_id' => $catElec->id, 'description' => 'Smartphones neufs et reconditionnés', 'is_active' => true]
        );

        $catInfo = Category::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'INFO'],
            ['name' => 'Informatique & PC', 'parent_id' => $catElec->id, 'description' => 'Ordinateurs portables et accessoires', 'is_active' => true]
        );

        $catAuto = Category::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'AUTO'],
            ['name' => 'Pièces & Accessoires Auto', 'description' => 'Pièces détachées d\'occasion certifiées', 'is_active' => true]
        );

        // 7. Products
        $p1 = Product::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'sku' => 'IPHONE-14P-128'],
            [
                'category_id' => $catTel->id,
                'base_unit_id' => $unitPce->id,
                'reference' => 'A2890',
                'manufacturer' => 'Apple',
                'name' => 'iPhone 14 Pro 128Go',
                'description' => 'Smartphone Apple écran 6.1" Super Retina XDR ProMotion, Dynamic Island.',
                'state' => 'used',
                'has_variants' => true,
                'requires_serial_number' => true,
                'alert_threshold' => 3,
                'is_active' => true,
            ]
        );

        ProductVariant::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $p1->id, 'sku' => 'IPHONE-14P-128-BLK'],
            [
                'name' => 'Noir Sidéral',
                'barcode' => '194253401234',
                'qr_code' => 'QR-IPHONE-BLK-128',
                'attribute_values' => ['Couleur' => 'Noir Sidéral', 'Capacité' => '128 Go'],
                'is_active' => true,
            ]
        );

        ProductVariant::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $p1->id, 'sku' => 'IPHONE-14P-128-GLD'],
            [
                'name' => 'Or',
                'barcode' => '194253401235',
                'qr_code' => 'QR-IPHONE-GLD-128',
                'attribute_values' => ['Couleur' => 'Or', 'Capacité' => '128 Go'],
                'is_active' => true,
            ]
        );

        $p2 = Product::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'sku' => 'DELL-5420-I7'],
            [
                'category_id' => $catInfo->id,
                'base_unit_id' => $unitPce->id,
                'reference' => 'LAT-5420-REF',
                'manufacturer' => 'Dell',
                'name' => 'Dell Latitude 5420 Core i7',
                'description' => 'PC Portable reconditionné grade A+, 16Go RAM, 512Go SSD NVMe, Windows 11 Pro.',
                'state' => 'refurbished',
                'has_variants' => false,
                'requires_serial_number' => true,
                'alert_threshold' => 2,
                'is_active' => true,
            ]
        );

        $p3 = Product::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'sku' => 'CABLE-USBC-100W'],
            [
                'category_id' => $catElec->id,
                'base_unit_id' => $unitPce->id,
                'reference' => 'CB-100W-2M',
                'manufacturer' => 'Baseus',
                'name' => 'Câble USB-C Power Delivery 100W 2m',
                'description' => 'Câble renforcé nylon tressé charge rapide 100W et transfert de données.',
                'state' => 'new',
                'has_variants' => false,
                'requires_serial_number' => false,
                'alert_threshold' => 10,
                'is_active' => true,
            ]
        );

        // 7b. Stock initial
        $defaultSite = \App\Models\Site::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SITE-MAIN'],
            ['name' => 'Site Principal Antananarivo', 'is_active' => true]
        );

        $defaultWarehouse = \App\Models\Warehouse::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'WH-MAIN'],
            ['site_id' => $defaultSite->id, 'name' => 'Magasin Principal', 'is_active' => true]
        );

        \App\Models\StockBalance::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $p1->id, 'warehouse_id' => $defaultWarehouse->id],
            ['on_hand' => 8]
        );
        \App\Models\StockBalance::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $p2->id, 'warehouse_id' => $defaultWarehouse->id],
            ['on_hand' => 4]
        );
        \App\Models\StockBalance::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $p3->id, 'warehouse_id' => $defaultWarehouse->id],
            ['on_hand' => 30]
        );

        // 8. Suppliers
        Supplier::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SUP-EUR-001'],
            [
                'company_name' => 'Fournisseur Europe Direct',
                'contact_name' => 'Jean Dupont',
                'email' => 'j.dupont@europe-export.eu',
                'phone' => '+33 1 23 45 67 89',
                'currency' => 'EUR',
                'tax_number' => 'FR32123456789',
                'address' => '12 Rue de l\'Industrie, 75001 Paris, France',
                'payment_terms_days' => 30,
                'is_active' => true,
            ]
        );

        Supplier::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SUP-MDG-002'],
            [
                'company_name' => 'Madagascar Import Express',
                'contact_name' => 'Aina Rakoto',
                'email' => 'aina@import-express.mg',
                'phone' => '+261 34 12 345 67',
                'currency' => 'MGA',
                'tax_number' => 'MG0012345678',
                'address' => 'Zone Industrielle Ankorondrano, Antananarivo',
                'payment_terms_days' => 15,
                'is_active' => true,
            ]
        );

        // 9. Customers
        Customer::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CUST-B2B-001'],
            [
                'company_name' => 'Société Tech Solutions SARL',
                'first_name' => 'Mamy',
                'last_name' => 'Andrianina',
                'email' => 'contact@techsolutions.mg',
                'phone' => '+261 32 00 111 22',
                'currency' => 'MGA',
                'tax_number' => 'MG9988776655',
                'address' => 'Analakely, Antananarivo',
                'credit_limit' => 10000000,
                'is_active' => true,
            ]
        );

        Customer::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CUST-B2C-002'],
            [
                'first_name' => 'Jean-Luc',
                'last_name' => 'Rabe',
                'email' => 'jl.rabe@gmail.com',
                'phone' => '+261 33 22 333 44',
                'currency' => 'MGA',
                'address' => 'Isoraka, Antananarivo',
                'credit_limit' => 0,
                'is_active' => true,
            ]
        );

        TenantContext::clear();
    }
}
