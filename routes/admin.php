<?php

use App\Http\Controllers\Admin\DMS\DmsController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Email\DefaultEmailTemplateController;
use App\Http\Controllers\Admin\Email\EmailProviderController;
use App\Http\Controllers\Admin\Email\SenderIdentityController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StuffController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ModuleMenuController;
use App\Http\Controllers\Admin\MasterDetailController;
use App\Http\Controllers\Admin\EmployeeTypeController;
use App\Http\Controllers\Admin\EmploymentStatusController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\SalaryComponentController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDocumentController;
use App\Http\Controllers\Admin\EmergencyContactController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\ExperienceController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ResignationController;
use App\Http\Controllers\Admin\TerminationController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AttendancePortalController;
use App\Http\Controllers\Admin\AttendanceWidgetController;
use App\Http\Controllers\Admin\HrmSettingsController;
use App\Http\Controllers\Admin\HrmDetailExportController;
use App\Http\Controllers\Admin\AttendanceAdjustmentController;
use App\Http\Controllers\Admin\LeaveBalanceController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\LeaveReportController;
use App\Http\Controllers\Admin\SalaryStructureController;
use App\Http\Controllers\Admin\SalaryTemplateController;
use App\Http\Controllers\Admin\MinimumWageRuleController;
use App\Http\Controllers\Admin\PayrollComplianceReportController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ExpenseClaimController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseClaimReportController;
use App\Http\Controllers\Admin\EmployeeLoanController;
use App\Http\Controllers\Admin\PerformanceReviewController;
use App\Http\Controllers\Admin\HrReportController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\DocumentationController;
use App\Http\Controllers\Admin\WorkflowTemplateController;
use App\Http\Controllers\Admin\WorkflowDefinitionController;
use App\Http\Controllers\Admin\WorkflowStatusController;
use App\Http\Controllers\Admin\WorkflowNotificationTriggerController;
use App\Http\Controllers\Admin\ApprovalDelegationController;
use App\Http\Controllers\Admin\LeadSourceController;
use App\Http\Controllers\Admin\LeadStatusController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\RelationshipHistoryController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\EmailCommunicationController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SalesForecastController;
use App\Http\Controllers\Admin\CrmDashboardController;
use App\Http\Controllers\Admin\CrmReportController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitConversionController;
use App\Http\Controllers\Admin\ProductAttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductBundleController;
use App\Http\Controllers\Admin\PriceTierController;
use App\Http\Controllers\Admin\ProductPriceController;
use App\Http\Controllers\Admin\DiscountRuleController;
use App\Http\Controllers\Admin\BarcodeGeneratorController;
use App\Http\Controllers\Admin\ProductReportController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerGroupController;
use App\Http\Controllers\Admin\PaymentTermController;
use App\Http\Controllers\Admin\PriceListController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\DeliveryNoteController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\SalesCommissionController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorGroupController;
use App\Http\Controllers\Admin\VendorPerformanceReviewController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\RfqController;
use App\Http\Controllers\Admin\SupplierQuotationController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Admin\DebitNoteController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\PurchaseReportController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\WarehouseLocationController;
use App\Http\Controllers\Admin\StockEntryController;
use App\Http\Controllers\Admin\OpeningStockController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\StockCountController;
use App\Http\Controllers\Admin\ProductBatchController;
use App\Http\Controllers\Admin\ProductSerialController;
use App\Http\Controllers\Admin\InventoryTransactionController;
use App\Http\Controllers\Admin\StockValuationController;
use App\Http\Controllers\Admin\ReorderLevelController;
use App\Http\Controllers\Admin\LowStockAlertController;
use App\Http\Controllers\Admin\InventoryReportController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\AccountTypeController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\GeneralLedgerController;
use App\Http\Controllers\Admin\TrialBalanceController;
use App\Http\Controllers\Admin\ProfitAndLossController;
use App\Http\Controllers\Admin\BalanceSheetController;
use App\Http\Controllers\Admin\CashFlowController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\AssetCategoryController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetPurchaseController;
use App\Http\Controllers\Admin\AssetAssignmentController;
use App\Http\Controllers\Admin\EmployeeAssetTrackingController;
use App\Http\Controllers\Admin\AssetLocationController;
use App\Http\Controllers\Admin\AssetLocationMovementController;
use App\Http\Controllers\Admin\AssetMaintenanceScheduleController;
use App\Http\Controllers\Admin\AssetMaintenanceRecordController;
use App\Http\Controllers\Admin\AssetDepreciationController;
use App\Http\Controllers\Admin\AssetDisposalController;
use App\Http\Controllers\Admin\AssetReportController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectBudgetController;
use App\Http\Controllers\Admin\ProjectTeamMemberController;
use App\Http\Controllers\Admin\ProjectMilestoneController;
use App\Http\Controllers\Admin\ProjectTaskController;
use App\Http\Controllers\Admin\TaskBoardController;
use App\Http\Controllers\Admin\GanttChartController;
use App\Http\Controllers\Admin\ProjectTaskDependencyController;
use App\Http\Controllers\Admin\ProjectTimeEntryController;
use App\Http\Controllers\Admin\ProjectTimesheetController;
use App\Http\Controllers\Admin\ProjectExpenseController;
use App\Http\Controllers\Admin\ProjectInvoiceController;
use App\Http\Controllers\Admin\ProjectReportController;
use App\Http\Controllers\Admin\TicketCategoryController;
use App\Http\Controllers\Admin\KnowledgeBaseCategoryController;
use App\Http\Controllers\Admin\KnowledgeBaseArticleController;
use App\Http\Controllers\Admin\SupportReportController;
use App\Http\Controllers\Admin\BudgetController;
use App\Http\Controllers\Admin\DepartmentBudgetController;
use App\Http\Controllers\Admin\FinanceProjectBudgetController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TicketAssignmentController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\Admin\TicketPriorityController;
use App\Http\Controllers\Admin\SlaPolicyController;
use App\Http\Controllers\Admin\EscalationRuleController;
use App\Http\Controllers\Admin\TicketEscalationController;
use App\Http\Controllers\Admin\CashBookController;
use App\Http\Controllers\Admin\FinanceBankAccountController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\AccountsReceivableController;
use App\Http\Controllers\Admin\AccountsPayableController;
use App\Http\Controllers\Admin\PaymentReceiveController;
use App\Http\Controllers\Admin\PaymentMakeController;
use App\Http\Controllers\Admin\SalesTargetController;
use App\Http\Controllers\Admin\SalesReturnController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SalesQuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('login/post', [LoginController::class, 'login'])->name('login.post');

// Physical/biometric attendance devices call this directly (no browser
// session, no login) — authenticated by the shared "Attendance Device
// Token" header instead (see AttendancePortalController::devicePunch()).
// Deliberately kept OUTSIDE the isAdmin group below: that middleware
// redirects anyone without an admin session to the login page, which
// would make this endpoint unreachable by a real external device. Also
// exempted from CSRF verification in bootstrap/app.php for the same
// reason (a device has no CSRF token to send).
Route::post('attendance-device/punch', [AttendancePortalController::class, 'devicePunch'])
    ->middleware('throttle:60,1')
    ->name('attendance-device.punch');

Route::middleware(['isAdmin'])->group(function () {
    Route::get('search', [GlobalSearchController::class, 'search'])->name('search');

    // Documentation
    Route::get('documentation', [DocumentationController::class, 'index'])->name('documentation.index');

    // ==========================================================================
    // Document Management.
    // ==========================================================================
    Route::prefix('dms')->name('dms.')->group(function () {
        Route::get('/', [DmsController::class, 'index'])->name('index');
        Route::get('data', [DmsController::class, 'data'])->name('data');
        Route::get('folder-tree', [DmsController::class, 'folderTree'])->name('folder-tree');
        Route::get('folders/create', [DmsController::class, 'createFolderForm'])->name('folders.create');
        Route::post('folders', [DmsController::class, 'storeFolder'])->name('folders.store');
        Route::post('upload', [DmsController::class, 'upload'])->name('upload');
        Route::post('{dms}/rename', [DmsController::class, 'rename'])->name('rename');
        Route::post('{dms}/favorite', [DmsController::class, 'toggleFavorite'])->name('favorite');
        Route::get('{dms}/download', [DmsController::class, 'download'])->name('download');
        Route::post('bulk/trash', [DmsController::class, 'bulkTrash'])->name('bulk-trash');
        Route::post('bulk/restore', [DmsController::class, 'bulkRestore'])->name('bulk-restore');
        Route::post('bulk/delete', [DmsController::class, 'bulkDeleteForever'])->name('bulk-delete');
        Route::post('bulk/move', [DmsController::class, 'bulkMove'])->name('bulk-move');
        Route::post('bulk/download', [DmsController::class, 'bulkDownload'])->name('bulk-download');
        Route::post('trash/empty', [DmsController::class, 'emptyTrash'])->name('empty-trash');
    });

    // Module Management
    Route::resource('modules', ModuleController::class)->except(['show']);
    Route::post('modules/{module}/activate', [ModuleController::class, 'activate'])->name('modules.activate');
    Route::post('modules/{module}/deactivate', [ModuleController::class, 'deactivate'])->name('modules.deactivate');
    Route::post('modules/{module}/install', [ModuleController::class, 'install'])->name('modules.install');
    Route::post('modules/status/{id}', [ModuleController::class, 'updateStatus'])->name('modules.status');
    Route::post('modules/{module}/uninstall', [ModuleController::class, 'uninstall'])->name('modules.uninstall');

    // Module Menu Management
    Route::get('module-menus/by-module', [ModuleMenuController::class, 'getByModule'])->name('module-menus.by-module');
    Route::post('module-menus/status/{id}', [ModuleMenuController::class, 'updateStatus'])->name('module-menus.status');
    Route::resource('module-menus', ModuleMenuController::class)->except(['show']);
    Route::put('module-menus/{moduleMenu}', [ModuleMenuController::class, 'update'])->name('module-menus.update');
    Route::delete('module-menus/{moduleMenu}', [ModuleMenuController::class, 'destroy'])->name('module-menus.destroy');
    Route::post('module-menus/{moduleMenu}/toggle-status', [ModuleMenuController::class, 'toggleStatus'])->name('module-menus.toggle-status');
    Route::post('module-menus/reorder', [ModuleMenuController::class, 'reorder'])->name('module-menus.reorder');

    // Workflow Engine - Workflow Templates
    Route::post('workflow-templates/status/{id}', [WorkflowTemplateController::class, 'updateStatus'])->name('workflow-templates.status');
    Route::resource('workflow-templates', WorkflowTemplateController::class)->except(['show']);

    // Workflow Engine - Workflow Definitions
    Route::post('workflow-definitions/status/{id}', [WorkflowDefinitionController::class, 'updateStatus'])->name('workflow-definitions.status');
    Route::resource('workflow-definitions', WorkflowDefinitionController::class)->except(['show']);

    // Workflow Engine - Workflow Statuses
    Route::resource('workflow-statuses', WorkflowStatusController::class)->except(['show']);

    // Workflow Engine - Workflow Notification Triggers
    Route::post('workflow-notification-triggers/status/{id}', [WorkflowNotificationTriggerController::class, 'updateStatus'])->name('workflow-notification-triggers.status');
    Route::resource('workflow-notification-triggers', WorkflowNotificationTriggerController::class)->except(['show']);

    // Workflow Engine - Approval Delegations
    Route::post('approval-delegations/status/{id}', [ApprovalDelegationController::class, 'updateStatus'])->name('approval-delegations.status');
    Route::resource('approval-delegations', ApprovalDelegationController::class)->except(['show']);

    // CRM - Lead Sources
    Route::post('lead-sources/status/{id}', [LeadSourceController::class, 'updateStatus'])->name('lead-sources.status');
    Route::resource('lead-sources', LeadSourceController::class)->except(['show']);

    // CRM - Lead Statuses
    Route::post('lead-statuses/status/{id}', [LeadStatusController::class, 'updateStatus'])->name('lead-statuses.status');
    Route::resource('lead-statuses', LeadStatusController::class)->except(['show']);

    // CRM - Leads
    Route::post('leads/status/{id}', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::resource('leads', LeadController::class)->except(['show']);

    // CRM - Contacts
    Route::post('contacts/status/{id}', [ContactController::class, 'updateStatus'])->name('contacts.status');
    Route::resource('contacts', ContactController::class)->except(['show']);

    // CRM - Companies
    Route::post('companies/status/{id}', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::resource('companies', CompanyController::class)->except(['show']);

    // CRM - Customer Relationship History
    Route::post('relationship-histories/status/{id}', [RelationshipHistoryController::class, 'updateStatus'])->name('relationship-histories.status');
    Route::resource('relationship-histories', RelationshipHistoryController::class)->except(['show']);

    // CRM - Opportunities & Sales Pipeline Kanban
    Route::get('opportunities/kanban', [OpportunityController::class, 'kanban'])->name('opportunities.kanban');
    Route::post('opportunities/{opportunity}/move-stage', [OpportunityController::class, 'moveStage'])->name('opportunities.move-stage');
    Route::post('opportunities/status/{id}', [OpportunityController::class, 'updateStatus'])->name('opportunities.status');
    Route::resource('opportunities', OpportunityController::class)->except(['show']);

    // CRM - Activities (Call, Meeting, Email)
    Route::post('activities/status/{id}', [ActivityController::class, 'updateStatus'])->name('activities.status');
    Route::post('activities/{activity}/activity-status', [ActivityController::class, 'updateActivityStatus'])->name('activities.activity-status');
    Route::resource('activities', ActivityController::class)->except(['show']);

    // CRM - Follow Ups and Reminders
    Route::post('follow-ups/status/{id}', [FollowUpController::class, 'updateStatus'])->name('follow-ups.status');
    Route::post('follow-ups/complete/{id}', [FollowUpController::class, 'updateCompleted'])->name('follow-ups.complete');
    Route::resource('follow-ups', FollowUpController::class)->except(['show']);

    // CRM - Email Communication History
    Route::post('email-communications/status/{id}', [EmailCommunicationController::class, 'updateStatus'])->name('email-communications.status');
    Route::resource('email-communications', EmailCommunicationController::class)->except(['show']);

    // CRM - Quotations
    Route::post('quotations/status/{id}', [QuotationController::class, 'updateStatus'])->name('quotations.status');
    Route::resource('quotations', QuotationController::class)->except(['show']);

    // CRM - Sales Forecasting
    Route::get('sales-forecast', [SalesForecastController::class, 'index'])->name('sales-forecast.index');

    // CRM - Dashboard
    Route::get('crm-dashboard', [CrmDashboardController::class, 'index'])->name('crm-dashboard.index');

    // CRM - Reports
    Route::get('crm-reports', [CrmReportController::class, 'index'])->name('crm-reports.index');

    // Product Management - Categories
    Route::post('categories/status/{id}', [CategoryController::class, 'updateStatus'])->name('categories.status');
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Product Management - Brands
    Route::post('brands/status/{id}', [BrandController::class, 'updateStatus'])->name('brands.status');
    Route::resource('brands', BrandController::class)->except(['show']);

    // Product Management - Units
    Route::post('units/status/{id}', [UnitController::class, 'updateStatus'])->name('units.status');
    Route::resource('units', UnitController::class)->except(['show']);

    // Product Management - Unit Conversion
    Route::post('unit-conversions/status/{id}', [UnitConversionController::class, 'updateStatus'])->name('unit-conversions.status');
    Route::resource('unit-conversions', UnitConversionController::class)->except(['show']);

    // Product Management - Product Attributes
    Route::post('product-attributes/status/{id}', [ProductAttributeController::class, 'updateStatus'])->name('product-attributes.status');
    Route::resource('product-attributes', ProductAttributeController::class)->except(['show']);

    // Product Management - Attribute Values
    Route::post('attribute-values/status/{id}', [AttributeValueController::class, 'updateStatus'])->name('attribute-values.status');
    Route::resource('attribute-values', AttributeValueController::class)->except(['show']);

    // Product Management - Products
    Route::post('products/status/{id}', [ProductController::class, 'updateStatus'])->name('products.status');
    Route::resource('products', ProductController::class)->except(['show']);

    // Product Management - Product Variants
    Route::post('product-variants/status/{id}', [ProductVariantController::class, 'updateStatus'])->name('product-variants.status');
    Route::resource('product-variants', ProductVariantController::class)->except(['show']);

    // Product Management - Product Images
    Route::post('product-images/status/{id}', [ProductImageController::class, 'updateStatus'])->name('product-images.status');
    Route::resource('product-images', ProductImageController::class)->except(['show']);

    // Product Management - Product Bundles
    Route::post('product-bundles/status/{id}', [ProductBundleController::class, 'updateStatus'])->name('product-bundles.status');
    Route::resource('product-bundles', ProductBundleController::class)->except(['show']);

    // Product Management - Pricing Management (Price Tiers + Product Prices)
    Route::post('price-tiers/status/{id}', [PriceTierController::class, 'updateStatus'])->name('price-tiers.status');
    Route::resource('price-tiers', PriceTierController::class)->except(['show']);
    Route::resource('product-prices', ProductPriceController::class)->except(['show']);

    // Product Management - Discount Rules
    Route::post('discount-rules/status/{id}', [DiscountRuleController::class, 'updateStatus'])->name('discount-rules.status');
    Route::resource('discount-rules', DiscountRuleController::class)->except(['show']);

    // Product Management - Barcode Generator
    Route::get('barcode-generator', [BarcodeGeneratorController::class, 'index'])->name('barcode-generator.index');
    Route::get('barcode-generator/print', [BarcodeGeneratorController::class, 'print'])->name('barcode-generator.print');

    // Product Management - Product Reports
    Route::get('product-reports', [ProductReportController::class, 'index'])->name('product-reports.index');

    // Sales - Customers
    Route::post('customers/status/{id}', [CustomerController::class, 'updateStatus'])->name('customers.status');
    Route::resource('customers', CustomerController::class)->except(['show']);

    // Sales - Customer Groups
    Route::post('customer-groups/status/{id}', [CustomerGroupController::class, 'updateStatus'])->name('customer-groups.status');
    Route::resource('customer-groups', CustomerGroupController::class)->except(['show']);

    // Sales - Payment Terms
    Route::post('payment-terms/status/{id}', [PaymentTermController::class, 'updateStatus'])->name('payment-terms.status');
    Route::resource('payment-terms', PaymentTermController::class)->except(['show']);

    // Sales - Price Lists
    Route::post('price-lists/status/{id}', [PriceListController::class, 'updateStatus'])->name('price-lists.status');
    Route::resource('price-lists', PriceListController::class)->except(['show']);

    // Sales - Sales Quotations
    Route::post('sales-quotations/status/{id}', [SalesQuotationController::class, 'updateStatus'])->name('sales-quotations.status');
    Route::resource('sales-quotations', SalesQuotationController::class)->except(['show']);

    // Sales - Sales Orders
    Route::post('sales-orders/status/{id}', [SalesOrderController::class, 'updateStatus'])->name('sales-orders.status');
    Route::resource('sales-orders', SalesOrderController::class)->except(['show']);

    // Sales - Delivery Management
    Route::post('deliveries/status/{id}', [DeliveryController::class, 'updateStatus'])->name('deliveries.status');
    Route::resource('deliveries', DeliveryController::class)->except(['show']);

    // Sales - Delivery Notes
    Route::post('delivery-notes/status/{id}', [DeliveryNoteController::class, 'updateStatus'])->name('delivery-notes.status');
    Route::get('delivery-notes/{delivery_note}/print', [DeliveryNoteController::class, 'print'])->name('delivery-notes.print');
    Route::resource('delivery-notes', DeliveryNoteController::class)->except(['show']);

    // Sales - Sales Invoices
    Route::post('sales-invoices/status/{id}', [SalesInvoiceController::class, 'updateStatus'])->name('sales-invoices.status');
    Route::resource('sales-invoices', SalesInvoiceController::class)->except(['show']);

    // Sales - Credit Notes
    Route::post('credit-notes/status/{id}', [CreditNoteController::class, 'updateStatus'])->name('credit-notes.status');
    Route::resource('credit-notes', CreditNoteController::class)->except(['show']);

    // Sales - Sales Returns
    Route::post('sales-returns/status/{id}', [SalesReturnController::class, 'updateStatus'])->name('sales-returns.status');
    Route::resource('sales-returns', SalesReturnController::class)->except(['show']);

    // Sales - POS System (no Route::resource - checkout only ever happens via the terminal, not a create/edit modal)
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/terminal', [PosController::class, 'terminal'])->name('pos.terminal');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('pos/status/{id}', [PosController::class, 'updateStatus'])->name('pos.status');
    Route::post('pos/{pos_sale}/void', [PosController::class, 'void'])->name('pos.void');
    Route::get('pos/{pos_sale}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::delete('pos/{pos_sale}', [PosController::class, 'destroy'])->name('pos.destroy');

    // Sales - Sales Targets
    Route::post('sales-targets/status/{id}', [SalesTargetController::class, 'updateStatus'])->name('sales-targets.status');
    Route::resource('sales-targets', SalesTargetController::class)->except(['show']);

    // Sales - Sales Commission
    Route::post('sales-commissions/status/{id}', [SalesCommissionController::class, 'updateStatus'])->name('sales-commissions.status');
    Route::post('sales-commissions/{sales_commission}/mark-paid', [SalesCommissionController::class, 'markAsPaid'])->name('sales-commissions.mark-paid');
    Route::resource('sales-commissions', SalesCommissionController::class)->except(['show']);

    // Sales - Sales Reports
    Route::get('sales-reports', [SalesReportController::class, 'index'])->name('sales-reports.index');

    // Purchase - Vendors
    Route::post('vendors/status/{id}', [VendorController::class, 'updateStatus'])->name('vendors.status');
    Route::resource('vendors', VendorController::class)->except(['show']);

    // Purchase - Vendor Groups
    Route::post('vendor-groups/status/{id}', [VendorGroupController::class, 'updateStatus'])->name('vendor-groups.status');
    Route::resource('vendor-groups', VendorGroupController::class)->except(['show']);

    // Purchase - Vendor Performance
    Route::post('vendor-performance-reviews/status/{id}', [VendorPerformanceReviewController::class, 'updateStatus'])->name('vendor-performance-reviews.status');
    Route::resource('vendor-performance-reviews', VendorPerformanceReviewController::class)->except(['show']);

    // Purchase - Purchase Requests
    Route::post('purchase-requests/status/{id}', [PurchaseRequestController::class, 'updateStatus'])->name('purchase-requests.status');
    Route::post('purchase-requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');
    Route::post('purchase-requests/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('purchase-requests.reject');
    Route::resource('purchase-requests', PurchaseRequestController::class)->except(['show']);

    // Purchase - RFQ Management
    Route::post('rfqs/status/{id}', [RfqController::class, 'updateStatus'])->name('rfqs.status');
    Route::resource('rfqs', RfqController::class)->except(['show']);

    // Purchase - Supplier Quotations
    Route::post('supplier-quotations/status/{id}', [SupplierQuotationController::class, 'updateStatus'])->name('supplier-quotations.status');
    Route::resource('supplier-quotations', SupplierQuotationController::class)->except(['show']);

    // Purchase - Purchase Orders
    Route::post('purchase-orders/status/{id}', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.status');
    Route::resource('purchase-orders', PurchaseOrderController::class)->except(['show']);

    // Purchase - Goods Receipts
    Route::post('goods-receipts/status/{id}', [GoodsReceiptController::class, 'updateStatus'])->name('goods-receipts.status');
    Route::resource('goods-receipts', GoodsReceiptController::class)->except(['show']);

    // Purchase - Purchase Invoice Matching
    Route::post('purchase-invoices/status/{id}', [PurchaseInvoiceController::class, 'updateStatus'])->name('purchase-invoices.status');
    Route::resource('purchase-invoices', PurchaseInvoiceController::class)->except(['show']);

    // Purchase - Debit Notes
    Route::post('debit-notes/status/{id}', [DebitNoteController::class, 'updateStatus'])->name('debit-notes.status');
    Route::resource('debit-notes', DebitNoteController::class)->except(['show']);

    // Purchase - Purchase Returns
    Route::post('purchase-returns/status/{id}', [PurchaseReturnController::class, 'updateStatus'])->name('purchase-returns.status');
    Route::resource('purchase-returns', PurchaseReturnController::class)->except(['show']);

    // Purchase - Purchase Reports
    Route::get('purchase-reports', [PurchaseReportController::class, 'index'])->name('purchase-reports.index');

    // Inventory - Warehouses
    Route::post('warehouses/status/{id}', [WarehouseController::class, 'updateStatus'])->name('warehouses.status');
    Route::resource('warehouses', WarehouseController::class)->except(['show']);

    // Inventory - Warehouse Locations
    Route::post('warehouse-locations/status/{id}', [WarehouseLocationController::class, 'updateStatus'])->name('warehouse-locations.status');
    Route::resource('warehouse-locations', WarehouseLocationController::class)->except(['show']);

    // Inventory - Stock Entry
    Route::post('stock-entries/status/{id}', [StockEntryController::class, 'updateStatus'])->name('stock-entries.status');
    Route::resource('stock-entries', StockEntryController::class)->except(['show']);

    // Inventory - Opening Stock
    Route::post('opening-stocks/status/{id}', [OpeningStockController::class, 'updateStatus'])->name('opening-stocks.status');
    Route::resource('opening-stocks', OpeningStockController::class)->except(['show']);

    // Inventory - Stock Adjustment
    Route::post('stock-adjustments/status/{id}', [StockAdjustmentController::class, 'updateStatus'])->name('stock-adjustments.status');
    Route::resource('stock-adjustments', StockAdjustmentController::class)->except(['show']);

    // Inventory - Stock Transfer
    Route::post('stock-transfers/status/{id}', [StockTransferController::class, 'updateStatus'])->name('stock-transfers.status');
    Route::resource('stock-transfers', StockTransferController::class)->except(['show']);

    // Inventory - Stock Count
    Route::post('stock-counts/status/{id}', [StockCountController::class, 'updateStatus'])->name('stock-counts.status');
    Route::resource('stock-counts', StockCountController::class)->except(['show']);

    // Inventory - Batch Management
    Route::post('product-batches/status/{id}', [ProductBatchController::class, 'updateStatus'])->name('product-batches.status');
    Route::resource('product-batches', ProductBatchController::class)->except(['show']);

    // Inventory - Serial Number Management
    Route::post('product-serials/status/{id}', [ProductSerialController::class, 'updateStatus'])->name('product-serials.status');
    Route::resource('product-serials', ProductSerialController::class)->except(['show']);

    // Inventory - Inventory Transactions
    Route::get('inventory-transactions', [InventoryTransactionController::class, 'index'])->name('inventory-transactions.index');

    // Inventory - Stock Valuation
    Route::get('stock-valuation', [StockValuationController::class, 'index'])->name('stock-valuation.index');

    // Inventory - Reorder Level
    Route::post('reorder-levels/status/{id}', [ReorderLevelController::class, 'updateStatus'])->name('reorder-levels.status');
    Route::resource('reorder-levels', ReorderLevelController::class)->except(['show']);

    // Inventory - Low Stock Alerts
    Route::get('low-stock-alerts', [LowStockAlertController::class, 'index'])->name('low-stock-alerts.index');

    // Inventory - Inventory Reports
    Route::get('inventory-reports', [InventoryReportController::class, 'index'])->name('inventory-reports.index');

    // Accounting - Chart of Accounts
    Route::post('chart-of-accounts/status/{id}', [ChartOfAccountController::class, 'updateStatus'])->name('chart-of-accounts.status');
    Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);

    // Accounting - Account Types
    Route::post('account-types/status/{id}', [AccountTypeController::class, 'updateStatus'])->name('account-types.status');
    Route::resource('account-types', AccountTypeController::class)->except(['show']);

    // Accounting - Journal Entries
    Route::post('journal-entries/status/{id}', [JournalEntryController::class, 'updateStatus'])->name('journal-entries.status');
    Route::resource('journal-entries', JournalEntryController::class)->except(['show']);

    // Accounting - General Ledger
    Route::get('general-ledger', [GeneralLedgerController::class, 'index'])->name('general-ledger.index');

    // Accounting - Cash Book
    Route::get('cash-book', [CashBookController::class, 'index'])->name('cash-book.index');

    // Accounting - Bank Accounts
    Route::post('finance-bank-accounts/status/{id}', [FinanceBankAccountController::class, 'updateStatus'])->name('finance-bank-accounts.status');
    Route::resource('finance-bank-accounts', FinanceBankAccountController::class)->except(['show']);

    // Accounting - Bank Transactions
    Route::post('bank-transactions/status/{id}', [BankTransactionController::class, 'updateStatus'])->name('bank-transactions.status');
    Route::resource('bank-transactions', BankTransactionController::class)->except(['show']);

    // Accounting - Bank Reconciliation
    Route::post('bank-reconciliations/status/{id}', [BankReconciliationController::class, 'updateStatus'])->name('bank-reconciliations.status');
    Route::resource('bank-reconciliations', BankReconciliationController::class)->except(['show']);

    // Accounting - Accounts Receivable
    Route::get('accounts-receivable', [AccountsReceivableController::class, 'index'])->name('accounts-receivable.index');

    // Accounting - Accounts Payable
    Route::get('accounts-payable', [AccountsPayableController::class, 'index'])->name('accounts-payable.index');

    // Accounting - Payment Receive
    Route::post('payment-receives/status/{id}', [PaymentReceiveController::class, 'updateStatus'])->name('payment-receives.status');
    Route::resource('payment-receives', PaymentReceiveController::class)->parameters(['payment-receives' => 'payment_receive'])->except(['show']);

    // Accounting - Payment Make
    Route::post('payment-makes/status/{id}', [PaymentMakeController::class, 'updateStatus'])->name('payment-makes.status');
    Route::resource('payment-makes', PaymentMakeController::class)->parameters(['payment-makes' => 'payment_make'])->except(['show']);

    // Accounting - Trial Balance
    Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');

    // Accounting - Profit and Loss
    Route::get('profit-and-loss', [ProfitAndLossController::class, 'index'])->name('profit-and-loss.index');

    // Accounting - Balance Sheet
    Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet.index');

    // Accounting - Cash Flow
    Route::get('cash-flow', [CashFlowController::class, 'index'])->name('cash-flow.index');

    // Accounting - Tax Management
    Route::post('tax-rates/status/{id}', [TaxRateController::class, 'updateStatus'])->name('tax-rates.status');
    Route::resource('tax-rates', TaxRateController::class)->except(['show']);

    // Accounting - Financial Reports
    Route::get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');

    // Asset Management - Asset Categories
    Route::post('asset-categories/status/{id}', [AssetCategoryController::class, 'updateStatus'])->name('asset-categories.status');
    Route::resource('asset-categories', AssetCategoryController::class)->except(['show']);

    // Asset Management - Asset Register
    Route::post('assets/status/{id}', [AssetController::class, 'updateStatus'])->name('assets.status');
    Route::resource('assets', AssetController::class)->except(['show']);

    // Asset Management - Asset Purchase Information
    Route::post('asset-purchases/status/{id}', [AssetPurchaseController::class, 'updateStatus'])->name('asset-purchases.status');
    Route::resource('asset-purchases', AssetPurchaseController::class)->except(['show']);

    // Asset Management - Asset Assignment
    Route::post('asset-assignments/status/{id}', [AssetAssignmentController::class, 'updateStatus'])->name('asset-assignments.status');
    Route::resource('asset-assignments', AssetAssignmentController::class)->except(['show']);

    // Asset Management - Employee Asset Tracking
    Route::get('employee-asset-tracking', [EmployeeAssetTrackingController::class, 'index'])->name('employee-asset-tracking.index');

    // Asset Management - Asset Location Tracking (Locations master + Movement tracking)
    Route::post('asset-locations/status/{id}', [AssetLocationController::class, 'updateStatus'])->name('asset-locations.status');
    Route::resource('asset-locations', AssetLocationController::class)->except(['show']);

    Route::post('asset-location-movements/status/{id}', [AssetLocationMovementController::class, 'updateStatus'])->name('asset-location-movements.status');
    Route::resource('asset-location-movements', AssetLocationMovementController::class)->except(['show']);

    // Asset Management - Maintenance Schedule
    Route::post('asset-maintenance-schedules/status/{id}', [AssetMaintenanceScheduleController::class, 'updateStatus'])->name('asset-maintenance-schedules.status');
    Route::resource('asset-maintenance-schedules', AssetMaintenanceScheduleController::class)->except(['show']);

    // Asset Management - Maintenance History
    Route::post('asset-maintenance-records/status/{id}', [AssetMaintenanceRecordController::class, 'updateStatus'])->name('asset-maintenance-records.status');
    Route::resource('asset-maintenance-records', AssetMaintenanceRecordController::class)->except(['show']);

    // Asset Management - Depreciation Calculation
    Route::get('asset-depreciation', [AssetDepreciationController::class, 'index'])->name('asset-depreciation.index');

    // Asset Management - Disposal Management
    Route::post('asset-disposals/status/{id}', [AssetDisposalController::class, 'updateStatus'])->name('asset-disposals.status');
    Route::resource('asset-disposals', AssetDisposalController::class)->except(['show']);

    // Asset Management - Asset Reports
    Route::get('asset-reports', [AssetReportController::class, 'index'])->name('asset-reports.index');

    // Project Management - Clients
    Route::post('clients/status/{id}', [ClientController::class, 'updateStatus'])->name('clients.status');
    Route::resource('clients', ClientController::class)->except(['show']);

    // Project Management - Projects
    Route::post('projects/status/{id}', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::resource('projects', ProjectController::class)->except(['show']);

    // Project Management - Project Budgets
    Route::post('project-budgets/status/{id}', [ProjectBudgetController::class, 'updateStatus'])->name('project-budgets.status');
    Route::resource('project-budgets', ProjectBudgetController::class)->except(['show']);

    // Project Management - Project Teams
    Route::post('project-team-members/status/{id}', [ProjectTeamMemberController::class, 'updateStatus'])->name('project-team-members.status');
    Route::resource('project-team-members', ProjectTeamMemberController::class)->except(['show']);

    // Project Management - Milestones
    Route::post('project-milestones/status/{id}', [ProjectMilestoneController::class, 'updateStatus'])->name('project-milestones.status');
    Route::resource('project-milestones', ProjectMilestoneController::class)->except(['show']);

    // Project Management - Tasks
    Route::post('project-tasks/status/{id}', [ProjectTaskController::class, 'updateStatus'])->name('project-tasks.status');
    Route::resource('project-tasks', ProjectTaskController::class)->except(['show']);

    // Project Management - Task Board Kanban
    Route::get('task-board', [TaskBoardController::class, 'index'])->name('task-board.index');
    Route::post('task-board/{project_task}/move', [TaskBoardController::class, 'move'])->name('task-board.move');

    // Project Management - Gantt Chart
    Route::get('gantt-chart', [GanttChartController::class, 'index'])->name('gantt-chart.index');

    // Project Management - Task Dependencies
    Route::post('project-task-dependencies/status/{id}', [ProjectTaskDependencyController::class, 'updateStatus'])->name('project-task-dependencies.status');
    Route::resource('project-task-dependencies', ProjectTaskDependencyController::class)->except(['show']);

    // Project Management - Time Tracking
    Route::post('project-time-entries/status/{id}', [ProjectTimeEntryController::class, 'updateStatus'])->name('project-time-entries.status');
    Route::get('project-time-entries/start-timer', [ProjectTimeEntryController::class, 'startTimerForm'])->name('project-time-entries.start-timer.form');
    Route::post('project-time-entries/start-timer', [ProjectTimeEntryController::class, 'startTimer'])->name('project-time-entries.start-timer');
    Route::post('project-time-entries/{project_time_entry}/stop-timer', [ProjectTimeEntryController::class, 'stopTimer'])->name('project-time-entries.stop-timer');
    Route::resource('project-time-entries', ProjectTimeEntryController::class)->except(['show']);

    // Project Management - Timesheets
    Route::post('project-timesheets/status/{id}', [ProjectTimesheetController::class, 'updateStatus'])->name('project-timesheets.status');
    Route::post('project-timesheets/{project_timesheet}/regenerate', [ProjectTimesheetController::class, 'regenerate'])->name('project-timesheets.regenerate');
    Route::post('project-timesheets/{project_timesheet}/submit', [ProjectTimesheetController::class, 'submitTimesheet'])->name('project-timesheets.submit');
    Route::post('project-timesheets/{project_timesheet}/approve', [ProjectTimesheetController::class, 'approveTimesheet'])->name('project-timesheets.approve');
    Route::post('project-timesheets/{project_timesheet}/reject', [ProjectTimesheetController::class, 'rejectTimesheet'])->name('project-timesheets.reject');
    Route::resource('project-timesheets', ProjectTimesheetController::class)->except(['show']);

    // Project Management - Project Expenses
    Route::post('project-expenses/status/{id}', [ProjectExpenseController::class, 'updateStatus'])->name('project-expenses.status');
    Route::post('project-expenses/{project_expense}/approve', [ProjectExpenseController::class, 'approve'])->name('project-expenses.approve');
    Route::post('project-expenses/{project_expense}/reject', [ProjectExpenseController::class, 'reject'])->name('project-expenses.reject');
    Route::resource('project-expenses', ProjectExpenseController::class)->except(['show']);

    // Project Management - Project Billing
    Route::post('project-invoices/status/{id}', [ProjectInvoiceController::class, 'updateStatus'])->name('project-invoices.status');
    Route::post('project-invoices/{project_invoice}/mark-sent', [ProjectInvoiceController::class, 'markSent'])->name('project-invoices.mark-sent');
    Route::post('project-invoices/{project_invoice}/record-payment', [ProjectInvoiceController::class, 'recordPayment'])->name('project-invoices.record-payment');
    Route::post('project-invoices/{project_invoice}/cancel', [ProjectInvoiceController::class, 'cancel'])->name('project-invoices.cancel');
    Route::resource('project-invoices', ProjectInvoiceController::class)->except(['show']);

    // Project Management - Project Profitability Reports
    Route::get('project-reports', [ProjectReportController::class, 'index'])->name('project-reports.index');

    // Support - Ticket Categories
    Route::post('ticket-categories/status/{id}', [TicketCategoryController::class, 'updateStatus'])->name('ticket-categories.status');
    Route::resource('ticket-categories', TicketCategoryController::class)->except(['show']);

    // Support - Ticket Creation
    Route::post('tickets/status/{id}', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('tickets/{ticket}/record-first-response', [TicketController::class, 'recordFirstResponse'])->name('tickets.record-first-response');
    Route::post('tickets/{ticket}/escalate', [TicketController::class, 'escalate'])->name('tickets.escalate');
    Route::resource('tickets', TicketController::class)->except(['show']);

    // Support - Ticket Assignment
    Route::post('ticket-assignments/status/{id}', [TicketAssignmentController::class, 'updateStatus'])->name('ticket-assignments.status');
    Route::resource('ticket-assignments', TicketAssignmentController::class)->except(['show']);

    // Support - Ticket Status
    Route::post('ticket-statuses/status/{id}', [TicketStatusController::class, 'updateStatus'])->name('ticket-statuses.status');
    Route::resource('ticket-statuses', TicketStatusController::class)->except(['show']);

    // Support - Priority Management
    Route::post('ticket-priorities/status/{id}', [TicketPriorityController::class, 'updateStatus'])->name('ticket-priorities.status');
    Route::resource('ticket-priorities', TicketPriorityController::class)->except(['show']);

    // Support - SLA Management
    Route::post('sla-policies/status/{id}', [SlaPolicyController::class, 'updateStatus'])->name('sla-policies.status');
    Route::resource('sla-policies', SlaPolicyController::class)->except(['show']);

    // Support - Escalation Rules
    Route::post('escalation-rules/status/{id}', [EscalationRuleController::class, 'updateStatus'])->name('escalation-rules.status');
    Route::resource('escalation-rules', EscalationRuleController::class)->except(['show']);

    // Support - Escalation History (read-only)
    Route::get('ticket-escalations', [TicketEscalationController::class, 'index'])->name('ticket-escalations.index');

    // Support - Knowledge Base (Categories master + Articles)
    Route::post('knowledge-base-categories/status/{id}', [KnowledgeBaseCategoryController::class, 'updateStatus'])->name('knowledge-base-categories.status');
    Route::resource('knowledge-base-categories', KnowledgeBaseCategoryController::class)->except(['show']);
    Route::post('knowledge-base-articles/status/{id}', [KnowledgeBaseArticleController::class, 'updateStatus'])->name('knowledge-base-articles.status');
    Route::resource('knowledge-base-articles', KnowledgeBaseArticleController::class)->except(['show']);

    // Support - Support Reports (read-only)
    Route::get('support-reports', [SupportReportController::class, 'index'])->name('support-reports.index');

    // Budget & Finance - Budget Planning
    Route::post('budgets/status/{id}', [BudgetController::class, 'updateStatus'])->name('budgets.status');
    Route::resource('budgets', BudgetController::class)->except(['show']);

    // Budget & Finance - Department Budget
    Route::post('department-budgets/status/{id}', [DepartmentBudgetController::class, 'updateStatus'])->name('department-budgets.status');
    Route::resource('department-budgets', DepartmentBudgetController::class)->except(['show']);

    // Budget & Finance - Project Budget
    Route::post('finance-project-budgets/status/{id}', [FinanceProjectBudgetController::class, 'updateStatus'])->name('finance-project-budgets.status');
    Route::resource('finance-project-budgets', FinanceProjectBudgetController::class)->except(['show']);

    /*
    |--------------------------------------------------------------------------
    | HRM permission enforcement
    |--------------------------------------------------------------------------
    |
    | Every HRM route below is gated by a "permission:{slug},admin" middleware
    | entry matching the exact permission names seeded in
    | database/seeders/RolePermissionSeeder.php. The Spatie PermissionMiddleware
    | is guard-aware (the second, comma-separated argument), so it always
    | checks the currently authenticated *admin* guard user, never the
    | unrelated default 'web' guard.
    |
    | A handful of routes are intentionally left ungated:
    |   - master-details.show / employees.find / designations.byDepartment —
    |     small cross-module AJAX helpers (offcanvas detail viewer, employee
    |     picker, dependent dropdown) reused by several already-gated parent
    |     screens; gating them individually risks breaking a picker on a
    |     screen whose own permission doesn't imply this one.
    |   - profile/* and attendance-portal/* — self-service "my own records"
    |     routes (an employee viewing/checking in their own attendance,
    |     documents, etc.), not administrative management of other people's
    |     records, so they must stay open to any authenticated admin.
    |
    | See App\Providers\AppServiceProvider::boot() for the Gate::before
    | Super Admin bypass that keeps this safe even if a slug here is ever
    | mistyped — Super Admin always passes regardless.
    |--------------------------------------------------------------------------
    */

    // HRM - Master details offcanvas (shared cross-module detail viewer)
    Route::get('master-details/{type}/{id}', [MasterDetailController::class, 'show'])->name('master-details.show');

    // HRM - HRM Settings
    Route::get('hrm-settings', [HrmSettingsController::class, 'index'])->name('hrm-settings.index')->middleware('permission:hrm-setting.view,admin');
    Route::put('hrm-settings', [HrmSettingsController::class, 'update'])->name('hrm-settings.update')->middleware('permission:hrm-setting.edit,admin');
    Route::get('hrm-settings/device-guide', [HrmSettingsController::class, 'deviceGuide'])->name('hrm-settings.device-guide')->middleware('permission:hrm-setting.view,admin');

    // HRM - Employee Types
    Route::get('employee-types/how-to', [EmployeeTypeController::class, 'howTo'])->name('employee-types.how.to')->middleware('permission:employee-type.view,admin');
    Route::post('employee-types/status/{id}', [EmployeeTypeController::class, 'updateStatus'])->name('employee-types.status')->middleware('permission:employee-type.edit,admin');
    Route::resource('employee-types', EmployeeTypeController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:employee-type.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:employee-type.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:employee-type.edit,admin')
        ->middlewareFor(['destroy'], 'permission:employee-type.delete,admin');

    // HRM - Employment Statuses
    Route::get('employment-statuses/how-to', [EmploymentStatusController::class, 'howTo'])->name('employment-statuses.how.to')->middleware('permission:employment-status.view,admin');
    Route::post('employment-statuses/status/{id}', [EmploymentStatusController::class, 'updateStatus'])->name('employment-statuses.status')->middleware('permission:employment-status.edit,admin');
    Route::resource('employment-statuses', EmploymentStatusController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:employment-status.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:employment-status.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:employment-status.edit,admin')
        ->middlewareFor(['destroy'], 'permission:employment-status.delete,admin');

    // HRM - Shifts
    Route::get('shifts/how-to', [ShiftController::class, 'howTo'])->name('shifts.how.to')->middleware('permission:shift.view,admin');
    Route::post('shifts/status/{id}', [ShiftController::class, 'updateStatus'])->name('shifts.status')->middleware('permission:shift.edit,admin');
    Route::resource('shifts', ShiftController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:shift.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:shift.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:shift.edit,admin')
        ->middlewareFor(['destroy'], 'permission:shift.delete,admin');

    // HRM - Holidays
    Route::get('holidays/how-to', [HolidayController::class, 'howTo'])->name('holidays.how.to')->middleware('permission:holiday.view,admin');
    Route::get('holidays/calendar', [HolidayController::class, 'calendar'])->name('holidays.calendar')->middleware('permission:holiday.view,admin');
    Route::get('holidays/calendar-events', [HolidayController::class, 'calendarEvents'])->name('holidays.calendar-events')->middleware('permission:holiday.view,admin');
    Route::post('holidays/status/{id}', [HolidayController::class, 'updateStatus'])->name('holidays.status')->middleware('permission:holiday.edit,admin');
    Route::resource('holidays', HolidayController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:holiday.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:holiday.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:holiday.edit,admin')
        ->middlewareFor(['destroy'], 'permission:holiday.delete,admin');

    // HRM - Leave Types
    Route::get('leave-types/{leaveType}/details', [HrmDetailExportController::class, 'leaveType'])->name('leave-types.details')->middleware('permission:leave-type.view,admin');
    Route::get('leave-types/{leaveType}/export', [HrmDetailExportController::class, 'leaveExport'])->name('leave-types.export')->middleware('permission:leave-type.view,admin');
    Route::get('leave-types/how-to', [LeaveTypeController::class, 'howTo'])->name('leave-types.how.to')->middleware('permission:leave-type.view,admin');
    Route::post('leave-types/status/{id}', [LeaveTypeController::class, 'updateStatus'])->name('leave-types.status')->middleware('permission:leave-type.edit,admin');
    Route::resource('leave-types', LeaveTypeController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:leave-type.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:leave-type.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:leave-type.edit,admin')
        ->middlewareFor(['destroy'], 'permission:leave-type.delete,admin');

    // HRM - Salary Components
    Route::get('salary-components/{salaryComponent}/details', [HrmDetailExportController::class, 'component'])->name('salary-components.details')->middleware('permission:salary-component.view,admin');
    Route::get('salary-components/{salaryComponent}/export', [HrmDetailExportController::class, 'componentExport'])->name('salary-components.export')->middleware('permission:salary-component.view,admin');
    Route::get('salary-components/{salaryComponent}/print', [HrmDetailExportController::class, 'componentPrint'])->name('salary-components.print')->middleware('permission:salary-component.view,admin');
    Route::get('salary-components/how-to', [SalaryComponentController::class, 'howTo'])->name('salary-components.how.to')->middleware('permission:salary-component.view,admin');
    Route::post('salary-components/status/{id}', [SalaryComponentController::class, 'updateStatus'])->name('salary-components.status')->middleware('permission:salary-component.edit,admin');
    Route::get('salary-components/{salaryComponent}/bulk-assign', [SalaryComponentController::class, 'bulkAssignForm'])->name('salary-components.bulk-assign-form')->middleware('permission:salary-component.edit,admin');
    Route::post('salary-components/{salaryComponent}/bulk-assign', [SalaryComponentController::class, 'bulkAssign'])->name('salary-components.bulk-assign')->middleware('permission:salary-component.edit,admin');
    Route::resource('salary-components', SalaryComponentController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:salary-component.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:salary-component.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:salary-component.edit,admin')
        ->middlewareFor(['destroy'], 'permission:salary-component.delete,admin');

    // HRM - Skills
    Route::post('skills/status/{id}', [SkillController::class, 'updateStatus'])->name('skills.status')->middleware('permission:skill.edit,admin');
    Route::resource('skills', SkillController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:skill.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:skill.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:skill.edit,admin')
        ->middlewareFor(['destroy'], 'permission:skill.delete,admin');

    // HRM - Employees
    Route::post('employees/status/{id}', [EmployeeController::class, 'updateStatus'])->name('employees.status')->middleware('permission:employee.edit,admin');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export')->middleware('permission:employee.view,admin');
    Route::get('employees/import', [EmployeeController::class, 'importForm'])->name('employees.import-form')->middleware('permission:employee.create,admin');
    Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import')->middleware('permission:employee.create,admin');
    Route::get('employees/{employee}/create-login', [EmployeeController::class, 'createLogin'])->name('employees.create-login')->middleware('permission:employee.edit,admin');
    Route::post('employees/{employee}/create-login', [EmployeeController::class, 'storeLogin'])->name('employees.store-login')->middleware('permission:employee.edit,admin');
    Route::get('employees/{employee}/salary-certificate-form', [EmployeeController::class, 'salaryCertificateForm'])->name('employees.salary-certificate-form')->middleware('permission:employee.view,admin');
    Route::get('employees/{employee}/salary-certificate', [EmployeeController::class, 'salaryCertificate'])->name('employees.salary-certificate')->middleware('permission:employee.view,admin');
    // Lightweight lookup used by dependent pickers on other, already-gated
    // screens (e.g. selecting an employee while creating a Leave Request) —
    // deliberately left ungated, see the note above this HRM block.
    Route::get('employees/find/{id}', [EmployeeController::class, 'findEmployee'])->name('employees.find');
    Route::resource('employees', EmployeeController::class)
        ->middlewareFor(['index', 'show'], 'permission:employee.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:employee.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:employee.edit,admin')
        ->middlewareFor(['destroy'], 'permission:employee.delete,admin');

    // HRM - Employee Documents
    Route::get('employee-documents/how-to', [EmployeeDocumentController::class, 'howTo'])->name('employee-documents.how.to')->middleware('permission:employee-document.view,admin');
    Route::post('employee-documents/status/{id}', [EmployeeDocumentController::class, 'updateStatus'])->name('employee-documents.status')->middleware('permission:employee-document.edit,admin');
    // Self-service — an employee viewing their OWN uploaded documents.
    Route::get('profile/documents', [EmployeeDocumentController::class, 'myDocuments'])->name('profile.documents');
    Route::resource('employee-documents', EmployeeDocumentController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:employee-document.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:employee-document.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:employee-document.edit,admin')
        ->middlewareFor(['destroy'], 'permission:employee-document.delete,admin');

    // HRM - Emergency Contacts
    Route::get('emergency-contacts/how-to', [EmergencyContactController::class, 'howTo'])->name('emergency-contacts.how.to')->middleware('permission:emergency-contact.view,admin');
    Route::post('emergency-contacts/status/{id}', [EmergencyContactController::class, 'updateStatus'])->name('emergency-contacts.status')->middleware('permission:emergency-contact.edit,admin');
    Route::get('profile/emergency-contacts', [EmergencyContactController::class, 'myEmergencyContacts'])->name('profile.emergency-contacts');
    Route::resource('emergency-contacts', EmergencyContactController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:emergency-contact.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:emergency-contact.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:emergency-contact.edit,admin')
        ->middlewareFor(['destroy'], 'permission:emergency-contact.delete,admin');

    // HRM - Bank Accounts
    Route::get('bank-accounts/how-to', [BankAccountController::class, 'howTo'])->name('bank-accounts.how.to')->middleware('permission:bank-account.view,admin');
    Route::post('bank-accounts/status/{id}', [BankAccountController::class, 'updateStatus'])->name('bank-accounts.status')->middleware('permission:bank-account.edit,admin');
    Route::get('profile/bank-accounts', [BankAccountController::class, 'myBankAccounts'])->name('profile.bank-accounts');
    Route::resource('bank-accounts', BankAccountController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:bank-account.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:bank-account.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:bank-account.edit,admin')
        ->middlewareFor(['destroy'], 'permission:bank-account.delete,admin');

    // HRM - Education
    Route::get('educations/how-to', [EducationController::class, 'howTo'])->name('educations.how.to')->middleware('permission:education.view,admin');
    Route::post('educations/status/{id}', [EducationController::class, 'updateStatus'])->name('educations.status')->middleware('permission:education.edit,admin');
    Route::get('profile/educations', [EducationController::class, 'myEducations'])->name('profile.educations');
    Route::resource('educations', EducationController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:education.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:education.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:education.edit,admin')
        ->middlewareFor(['destroy'], 'permission:education.delete,admin');

    // HRM - Experience
    Route::get('experiences/how-to', [ExperienceController::class, 'howTo'])->name('experiences.how.to')->middleware('permission:experience.view,admin');
    Route::post('experiences/status/{id}', [ExperienceController::class, 'updateStatus'])->name('experiences.status')->middleware('permission:experience.edit,admin');
    Route::get('profile/experiences', [ExperienceController::class, 'myExperience'])->name('profile.experiences');
    Route::resource('experiences', ExperienceController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:experience.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:experience.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:experience.edit,admin')
        ->middlewareFor(['destroy'], 'permission:experience.delete,admin');

    // HRM - Transfers
    Route::get('transfers/how-to', [TransferController::class, 'howTo'])->name('transfers.how.to')->middleware('permission:transfer.view,admin');
    Route::post('transfers/status/{id}', [TransferController::class, 'updateStatus'])->name('transfers.status')->middleware('permission:transfer.edit,admin');
    Route::resource('transfers', TransferController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:transfer.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:transfer.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:transfer.edit,admin')
        ->middlewareFor(['destroy'], 'permission:transfer.delete,admin');

    // HRM - Promotions
    Route::get('promotions/how-to', [PromotionController::class, 'howTo'])->name('promotions.how.to')->middleware('permission:promotion.view,admin');
    Route::post('promotions/status/{id}', [PromotionController::class, 'updateStatus'])->name('promotions.status')->middleware('permission:promotion.edit,admin');
    Route::resource('promotions', PromotionController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:promotion.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:promotion.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:promotion.edit,admin')
        ->middlewareFor(['destroy'], 'permission:promotion.delete,admin');

    // HRM - Resignations
    Route::get('resignations/how-to', [ResignationController::class, 'howTo'])->name('resignations.how.to')->middleware('permission:resignation.view,admin');
    Route::post('resignations/status/{id}', [ResignationController::class, 'updateStatus'])->name('resignations.status')->middleware('permission:resignation.edit,admin');
    Route::resource('resignations', ResignationController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:resignation.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:resignation.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:resignation.edit,admin')
        ->middlewareFor(['destroy'], 'permission:resignation.delete,admin');

    // HRM - Terminations
    Route::get('terminations/how-to', [TerminationController::class, 'howTo'])->name('terminations.how.to')->middleware('permission:termination.view,admin');
    Route::post('terminations/status/{id}', [TerminationController::class, 'updateStatus'])->name('terminations.status')->middleware('permission:termination.edit,admin');
    Route::resource('terminations', TerminationController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:termination.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:termination.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:termination.edit,admin')
        ->middlewareFor(['destroy'], 'permission:termination.delete,admin');

    // HRM - Attendance
    // Self-service kiosk — an employee checking THEMSELVES in/out, resolved
    // from their own linked employee record, never someone else's.
    Route::get('attendance-portal', [AttendancePortalController::class, 'portal'])->name('attendance-portal.index');
    Route::post('attendance-portal/check-in', [AttendancePortalController::class, 'checkIn'])->name('attendance-portal.check-in');
    Route::post('attendance-portal/check-out', [AttendancePortalController::class, 'checkOut'])->name('attendance-portal.check-out');
    // Header attendance widget's own refresh call (see AttendanceWidgetController)
    // — same self-service, ungated reasoning as the portal routes above.
    Route::get('attendance-widget/status', [AttendanceWidgetController::class, 'status'])->name('attendance-widget.status');
    // Self-service "Request Adjustment" for one of the employee's own past
    // days — reuses the existing AttendanceAdjustment model/service, same
    // ungated self-service reasoning as the routes above.
    Route::get('attendance-portal/adjustment', [AttendancePortalController::class, 'adjustmentForm'])->name('attendance-portal.adjustment.form');
    Route::post('attendance-portal/adjustment', [AttendancePortalController::class, 'storeAdjustment'])->name('attendance-portal.adjustment.store');
    // Unlike the portal above, this accepts an arbitrary employee_id — it's
    // an admin report over any employee's month, not a self-service view.
    Route::get('attendance-monthly', [AttendancePortalController::class, 'monthly'])->name('attendances.monthly')->middleware('permission:attendance.view,admin');
    Route::post('attendances/status/{id}', [AttendanceController::class, 'updateStatus'])->name('attendances.status')->middleware('permission:attendance.edit,admin');
    Route::resource('attendances', AttendanceController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:attendance.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:attendance.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:attendance.edit,admin')
        ->middlewareFor(['destroy'], 'permission:attendance.delete,admin');

    // HRM - Attendance Adjustments
    Route::post('attendance-adjustments/status/{id}', [AttendanceAdjustmentController::class, 'updateStatus'])->name('attendance-adjustments.status')->middleware('permission:attendance-adjustment.edit,admin');
    Route::post('attendance-adjustments/{attendanceAdjustment}/approve', [AttendanceAdjustmentController::class, 'approve'])->name('attendance-adjustments.approve')->middleware('permission:attendance-adjustment.approve,admin');
    Route::post('attendance-adjustments/{attendanceAdjustment}/reject', [AttendanceAdjustmentController::class, 'reject'])->name('attendance-adjustments.reject')->middleware('permission:attendance-adjustment.reject,admin');
    Route::resource('attendance-adjustments', AttendanceAdjustmentController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:attendance-adjustment.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:attendance-adjustment.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:attendance-adjustment.edit,admin')
        ->middlewareFor(['destroy'], 'permission:attendance-adjustment.delete,admin');

    // HRM - Leave Balances
    Route::post('leave-balances/status/{id}', [LeaveBalanceController::class, 'updateStatus'])->name('leave-balances.status')->middleware('permission:leave-balance.edit,admin');
    Route::post('leave-balances/generate', [LeaveBalanceController::class, 'generate'])->name('leave-balances.generate')->middleware('permission:leave-balance.generate,admin');
    Route::post('leave-balances/{leaveBalance}/encash', [LeaveBalanceController::class, 'encash'])->name('leave-balances.encash')->middleware('permission:leave-balance.encash,admin');
    Route::resource('leave-balances', LeaveBalanceController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:leave-balance.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:leave-balance.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:leave-balance.edit,admin')
        ->middlewareFor(['destroy'], 'permission:leave-balance.delete,admin');

    // HRM - Leave Requests
    Route::get('leave-requests/calendar', [LeaveRequestController::class, 'calendar'])->name('leave-requests.calendar')->middleware('permission:leave-request.view,admin');
    Route::get('leave-requests/calendar-events', [LeaveRequestController::class, 'calendarEvents'])->name('leave-requests.calendar-events')->middleware('permission:leave-request.view,admin');
    Route::post('leave-requests/status/{id}', [LeaveRequestController::class, 'updateStatus'])->name('leave-requests.status')->middleware('permission:leave-request.edit,admin');
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve')->middleware('permission:leave-request.approve,admin');
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject')->middleware('permission:leave-request.reject,admin');
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel')->middleware('permission:leave-request.cancel,admin');
    Route::resource('leave-requests', LeaveRequestController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:leave-request.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:leave-request.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:leave-request.edit,admin')
        ->middlewareFor(['destroy'], 'permission:leave-request.delete,admin');

    // HRM - Leave Reports (Phase F3)
    Route::get('leave-reports', [LeaveReportController::class, 'index'])->name('leave-reports.index')->middleware('permission:leave-report.view,admin');

    // HRM - Salary Structures
    Route::get('salary-structures/how-to', [SalaryStructureController::class, 'howTo'])->name('salary-structures.how.to')->middleware('permission:salary-structure.view,admin');
    Route::post('salary-structures/status/{id}', [SalaryStructureController::class, 'updateStatus'])->name('salary-structures.status')->middleware('permission:salary-structure.edit,admin');
    Route::resource('salary-structures', SalaryStructureController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:salary-structure.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:salary-structure.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:salary-structure.edit,admin')
        ->middlewareFor(['destroy'], 'permission:salary-structure.delete,admin');

    // HRM - Salary Templates (bulk-assignable salary templates for employee groups)
    Route::get('salary-templates/how-to', [SalaryTemplateController::class, 'howTo'])->name('salary-templates.how.to')->middleware('permission:salary-template.view,admin');
    Route::post('salary-templates/status/{id}', [SalaryTemplateController::class, 'updateStatus'])->name('salary-templates.status')->middleware('permission:salary-template.edit,admin');
    Route::get('salary-templates/{salaryTemplate}/assign', [SalaryTemplateController::class, 'assignForm'])->name('salary-templates.assign-form')->middleware('permission:salary-template.assign,admin');
    Route::post('salary-templates/{salaryTemplate}/assign', [SalaryTemplateController::class, 'assign'])->name('salary-templates.assign')->middleware('permission:salary-template.assign,admin');
    Route::resource('salary-templates', SalaryTemplateController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:salary-template.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:salary-template.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:salary-template.edit,admin')
        ->middlewareFor(['destroy'], 'permission:salary-template.delete,admin');

    // HRM - Minimum Wage Rules (per country/state compliance floor)
    Route::post('minimum-wage-rules/status/{id}', [MinimumWageRuleController::class, 'updateStatus'])->name('minimum-wage-rules.status')->middleware('permission:minimum-wage-rule.edit,admin');
    Route::resource('minimum-wage-rules', MinimumWageRuleController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:minimum-wage-rule.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:minimum-wage-rule.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:minimum-wage-rule.edit,admin')
        ->middlewareFor(['destroy'], 'permission:minimum-wage-rule.delete,admin');

    // HRM - Payroll Compliance Report (read-only)
    Route::get('payroll-compliance-report', [PayrollComplianceReportController::class, 'index'])->name('payroll-compliance-report.index')->middleware('permission:payroll-compliance-report.view,admin');

    // HRM - Payroll
    Route::post('payrolls/status/{id}', [PayrollController::class, 'updateStatus'])->name('payrolls.status')->middleware('permission:payroll.create,admin');
    Route::post('payrolls/{payroll}/mark-paid', [PayrollController::class, 'markAsPaid'])->name('payrolls.mark-paid')->middleware('permission:payroll.mark-paid,admin');
    Route::get('payrolls/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payrolls.payslip')->middleware('permission:payroll.view,admin');
    Route::get('payrolls/bulk-generate', [PayrollController::class, 'bulkGenerateForm'])->name('payrolls.bulk-generate-form')->middleware('permission:payroll.bulk-generate,admin');
    Route::post('payrolls/bulk-generate', [PayrollController::class, 'bulkGenerate'])->name('payrolls.bulk-generate')->middleware('permission:payroll.bulk-generate,admin');
    Route::get('payrolls/how-to', [PayrollController::class, 'howTo'])->name('payrolls.how.to')->middleware('permission:payroll.view,admin');
    Route::resource('payrolls', PayrollController::class)->except(['show', 'edit', 'update'])
        ->middlewareFor(['index'], 'permission:payroll.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:payroll.create,admin')
        ->middlewareFor(['destroy'], 'permission:payroll.delete,admin');

    // HRM - Expense Categories (master for Expense Claims)
    Route::post('expense-categories/status/{id}', [ExpenseCategoryController::class, 'updateStatus'])->name('expense-categories.status')->middleware('permission:expense-category.edit,admin');
    Route::get('expense-categories/how-to', [ExpenseCategoryController::class, 'howTo'])->name('expense-categories.how.to')->middleware('permission:expense-category.view,admin');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:expense-category.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:expense-category.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:expense-category.edit,admin')
        ->middlewareFor(['destroy'], 'permission:expense-category.delete,admin');

    // HRM - Expense Claims
    Route::post('expense-claims/status/{id}', [ExpenseClaimController::class, 'updateStatus'])->name('expense-claims.status')->middleware('permission:expense-claim.edit,admin');
    Route::post('expense-claims/{expenseClaim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-claims.approve')->middleware('permission:expense-claim.approve,admin');
    Route::post('expense-claims/{expenseClaim}/reject', [ExpenseClaimController::class, 'reject'])->name('expense-claims.reject')->middleware('permission:expense-claim.reject,admin');
    Route::post('expense-claims/{expenseClaim}/mark-reimbursed', [ExpenseClaimController::class, 'markReimbursed'])->name('expense-claims.mark-reimbursed')->middleware('permission:expense-claim.approve,admin');
    Route::get('expense-claims/how-to', [ExpenseClaimController::class, 'howTo'])->name('expense-claims.how.to')->middleware('permission:expense-claim.view,admin');
    Route::resource('expense-claims', ExpenseClaimController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:expense-claim.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:expense-claim.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:expense-claim.edit,admin')
        ->middlewareFor(['destroy'], 'permission:expense-claim.delete,admin');

    // HRM - Expense Claims Report
    Route::get('expense-claims-report', [ExpenseClaimReportController::class, 'index'])->name('expense-claims-report.index')->middleware('permission:expense-claim.view,admin');

    // HRM - Employee Loans
    Route::post('employee-loans/status/{id}', [EmployeeLoanController::class, 'updateStatus'])->name('employee-loans.status')->middleware('permission:employee-loan.edit,admin');
    Route::post('employee-loans/{employeeLoan}/approve', [EmployeeLoanController::class, 'approve'])->name('employee-loans.approve')->middleware('permission:employee-loan.approve,admin');
    Route::post('employee-loans/{employeeLoan}/reject', [EmployeeLoanController::class, 'reject'])->name('employee-loans.reject')->middleware('permission:employee-loan.reject,admin');
    Route::post('employee-loans/{employeeLoan}/record-payment', [EmployeeLoanController::class, 'recordPayment'])->name('employee-loans.record-payment')->middleware('permission:employee-loan.record-payment,admin');
    Route::resource('employee-loans', EmployeeLoanController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:employee-loan.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:employee-loan.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:employee-loan.edit,admin')
        ->middlewareFor(['destroy'], 'permission:employee-loan.delete,admin');

    // HRM - Performance Reviews
    Route::post('performance-reviews/status/{id}', [PerformanceReviewController::class, 'updateStatus'])->name('performance-reviews.status')->middleware('permission:performance-review.edit,admin');
    Route::resource('performance-reviews', PerformanceReviewController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:performance-review.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:performance-review.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:performance-review.edit,admin')
        ->middlewareFor(['destroy'], 'permission:performance-review.delete,admin');

    // HRM - HR Reports
    Route::get('hr-reports', [HrReportController::class, 'index'])->name('hr-reports.index')->middleware('permission:hr-report.view,admin');

    // HRM - Departments
    Route::get('departments/how-to', [DepartmentController::class, 'howTo'])->name('departments.how.to')->middleware('permission:department.view,admin');
    Route::post('departments/status/{id}', [DepartmentController::class, 'updateStatus'])->name('departments.status')->middleware('permission:department.edit,admin');
    Route::resource('departments', DepartmentController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:department.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:department.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:department.edit,admin')
        ->middlewareFor(['destroy'], 'permission:department.delete,admin');

    // HRM - Designations
    Route::get('designations/how-to', [DesignationController::class, 'howTo'])->name('designations.how.to')->middleware('permission:designation.view,admin');
    Route::post('designations/status/{id}', [DesignationController::class, 'updateStatus'])->name('designations.status')->middleware('permission:designation.edit,admin');
    // Dependent-dropdown helper (department -> designations), reused by
    // other already-gated screens — deliberately left ungated.
    Route::get('designations/by-department/{department}', [DesignationController::class, 'getByDepartment'])->name('designations.byDepartment');
    Route::resource('designations', DesignationController::class)->except(['show'])
        ->middlewareFor(['index'], 'permission:designation.view,admin')
        ->middlewareFor(['create', 'store'], 'permission:designation.create,admin')
        ->middlewareFor(['edit', 'update'], 'permission:designation.edit,admin')
        ->middlewareFor(['destroy'], 'permission:designation.delete,admin');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::post('/languages/delete-selected', [LanguageController::class, 'deleteSelected'])->name('languages.bulk-delete');
    Route::get('language/{language}/translations', [LanguageController::class, 'translate'])->name('languages.translations');
    Route::post('language/{language}/translations', [LanguageController::class, 'updateTranslation'])->name('languages.translations.update');
    Route::post('change-language', [LanguageController::class, 'changeLanguage'])->name('change-language');
    Route::resource('languages', LanguageController::class);

    Route::get('/api/notifications', [NotificationController::class, 'index']);
    Route::get('/api/notifications/stream', [NotificationController::class, 'stream']);
    Route::post('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/test-log', [NotificationController::class, 'createDummyLog']);
    Route::get('/notifications-page', [NotificationController::class, 'notificationsPage'])->name('notifications');
    Route::delete('/api/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/api/notifications/delete-selected', [NotificationController::class, 'deleteSelected']);

    Route::get('stuff/edit-password/{id}', [StuffController::class, 'editPassword'])->name('stuff.edit.password');
    Route::patch('stuff/update-password/{id}', [StuffController::class, 'updatePassword'])->name('stuff.update.password');
    Route::post('stuff/status/{id}', [StuffController::class, 'updateStatus'])->name('stuff.status');
    Route::resource('stuff', StuffController::class);

    Route::get('/users/{user}/impersonate', [ImpersonateController::class,'start'])->name('users.impersonate');
    Route::get('/impersonate/stop',[ImpersonateController::class,'stop'])->name('impersonate.stop');

    Route::get('roles/assign/{id}', [RoleController::class, 'assign'])->name('roles.assign');
    Route::post('roles/assign/{id}', [RoleController::class, 'assignUpdate'])->name('roles.assign.update');
    Route::post('roles/status/{id}', [RoleController::class, 'updateStatus'])->name('roles.status');
    Route::resource('roles', RoleController::class);

    Route::get('activity-logs', [ActivityLogController::class,'index'])->name('activity.logs');
    Route::get('activity-logs/data', [ActivityLogController::class,'data'])->name('activity.logs.data');
    Route::get('activity-logs/{id}', [ActivityLogController::class,'show'])->name('activity.logs.show');

    Route::get('sitemap', [SettingsController::class, 'sitemap'])->name('sitemap');
    Route::get('profile', [SettingsController::class, 'myProfile'])->name('profile');
    Route::get('edit-profile', [SettingsController::class, 'editProfile'])->name('edit.profile');
    Route::post('update-profile', [SettingsController::class, 'updateProfile'])->name('update.profile');
    Route::get('edit-password', [SettingsController::class, 'editPassword'])->name('edit.password');
    Route::post('update-password', [SettingsController::class, 'updatePassword'])->name('update.password');

    Route::post('/system/optimize', [SettingsController::class, 'optimize'])->name('admin.system.optimize');
    Route::get('branding', [SettingsController::class, 'branding'])->name('settings.branding');
    Route::get('company', [SettingsController::class, 'company'])->name('settings.company');
    Route::get('appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
    Route::get('localization', [SettingsController::class, 'localization'])->name('settings.localization');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('activity/{id}', [SettingsController::class, 'showActivity'])->name('activity.show');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.post');

    Route::prefix('email')->name('email.')->group(function () {

        Route::resource('email-templates', DefaultEmailTemplateController::class)
        ->except(['show']); // show not used

        // Additional custom routes
        Route::post('email-templates/{id}/duplicate', [DefaultEmailTemplateController::class, 'duplicate'])
            ->name('email-templates.duplicate');
        Route::post('email-templates/{id}/change-status', [DefaultEmailTemplateController::class, 'changeStatus'])
            ->name('email-templates.change-status');
        Route::get('email-templates/{id}/preview', [DefaultEmailTemplateController::class, 'preview'])
            ->name('email-templates.preview');


        // Provider routes
        Route::resource('providers', EmailProviderController::class)->except(['show']);
        Route::post('providers/{id}/status', [EmailProviderController::class, 'updateStatus'])->name('providers.status');
        Route::put('providers/{id}/default', [EmailProviderController::class, 'setDefault'])->name('providers.default');
        Route::post('providers/{id}/test-connection', [EmailProviderController::class, 'testConnection'])->name('providers.test-connection');
        Route::get('providers/{id}/logs', [EmailProviderController::class, 'logs'])->name('providers.logs');

        // Sender identities (nested under provider)
        Route::prefix('sender-identities')->name('sender-identities.')->group(function () {
            Route::get('/', [SenderIdentityController::class, 'index'])->name('index');
            Route::get('/create', [SenderIdentityController::class, 'create'])->name('create');
            Route::post('/', [SenderIdentityController::class, 'store'])->name('store');
            Route::get('/{identity}/edit', [SenderIdentityController::class, 'edit'])->name('edit');
            Route::patch('/{identity}', [SenderIdentityController::class, 'update'])->name('update');
            Route::delete('/{identity}', [SenderIdentityController::class, 'destroy'])->name('destroy');
            Route::put('/{identity}/default', [SenderIdentityController::class, 'setDefault'])->name('default');
            Route::get('{identity}/test-email', [SenderIdentityController::class, 'testEmail'])->name('test-email');
            Route::post('{identity}/test-email', [SenderIdentityController::class, 'sendTestEmail'])->name('test-email');
        });
    });

    Route::get('release-history', [SettingsController::class, 'releaseHistory'])->name('release.history');
});
