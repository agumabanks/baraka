<?php

namespace Database\Seeders;

use Database\Seeders\Backend\FrontWeb\BlogSeeder;
use Database\Seeders\Backend\FrontWeb\FaqSeeder;
use Database\Seeders\Backend\FrontWeb\PageSeeder;
use Database\Seeders\Backend\FrontWeb\PartnerSeeder;
use Database\Seeders\Backend\FrontWeb\SectionSeeder;
use Database\Seeders\Backend\FrontWeb\ServiceSeeder;
use Database\Seeders\Backend\FrontWeb\SocialLinkSeeder;
use Database\Seeders\Backend\FrontWeb\WhyCourierSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UploadSeeder::class);
        $this->call(HubSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(DesignationSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(DeliveryManSeeder::class);
        $this->call(HubInChargeSeeder::class);
        $this->call(DeliverycategorySeeder::class);
        $this->call(DeliveryChargeSeeder::class);
        $this->call(MerchantSeeder::class);
        $this->call(MerchantshopsSeeder::class);
        // $this->call(MerchantPaymentSeeder::class);
        // $this->call(AccountSeeder::class);
        // $this->call(MerchantManagePaymentSeeder::class);
        // $this->call(FundTransferSeeder::class);
        // $this->call(PaymentAccountSeeder::class);
        $this->call(ConfigSeeder::class);
        $this->call(PackagingSeeder::class);
        // $this->call(ParcelSeeder::class);
        $this->call(AccountHeadSeeder::class);
        // $this->call(ExpenseSeeder::class);

        $this->call(PermissionSeeder::class);
        // $this->call(IncomeSeeder::class);
        $this->call(SmsSettingSeeder::class);
        $this->call(SmsSendSettingsSeeder::class);
        $this->call(GeneralSettingsSeeder::class);
        $this->call(NotificationSettingsSeeder::class);
        // $this->call(SalaryGenerateSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(MerchantSettingSeeder::class);
        $this->call(CurrencySeeder::class);

        // front web seeder
        $this->call(SocialLinkSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(WhyCourierSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(PartnerSeeder::class);
        $this->call(BlogSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(SectionSeeder::class);

        // ERP modules permissions and role mappings
        $this->call(ErpPermissionSeeder::class);
        $this->call(ErpRoleMapSeeder::class);

        // DHL-grade modules seeds
        $this->call(SurchargeRuleSeeder::class);
        $this->call(SortationAndWarehouseSeeder::class);
        $this->call(AwbStockSeeder::class);
        $this->call(ZonesAndLanesSeeder::class);
        $this->call(CarriersAndServicesSeeder::class);
        $this->call(TransportLegSeeder::class);

        // Ensure an admin user exists with password 'admin'
        $this->call(AdminUserSeeder::class);
    }
}
