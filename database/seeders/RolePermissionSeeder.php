<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [

            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            'role.view',
            'role.create',
            'role.edit',
            'role.delete',

            'settings.view',

            // Base Admin Panel - Document Management System (DMS)
            // Hardcoded sidebar feature, not a dynamic module — permissions
            // defined here for consistency with every other module, though
            // enforcement is currently disabled project-wide (see notes).
            'dms.view',
            'dms.create',
            'dms.edit',
            'dms.delete',

            // Workflow Engine
            'workflow-template.view',
            'workflow-template.create',
            'workflow-template.edit',
            'workflow-template.delete',

            'workflow-definition.view',
            'workflow-definition.create',
            'workflow-definition.edit',
            'workflow-definition.delete',

            'workflow-status.view',
            'workflow-status.create',
            'workflow-status.edit',
            'workflow-status.delete',

            'workflow-notification-trigger.view',
            'workflow-notification-trigger.create',
            'workflow-notification-trigger.edit',
            'workflow-notification-trigger.delete',

            'approval-delegation.view',
            'approval-delegation.create',
            'approval-delegation.edit',
            'approval-delegation.delete',

            'employee-type.view',
            'employee-type.create',
            'employee-type.edit',
            'employee-type.delete',

            'employment-status.view',
            'employment-status.create',
            'employment-status.edit',
            'employment-status.delete',

            'shift.view',
            'shift.create',
            'shift.edit',
            'shift.delete',

            'holiday.view',
            'holiday.create',
            'holiday.edit',
            'holiday.delete',

            'leave-type.view',
            'leave-type.create',
            'leave-type.edit',
            'leave-type.delete',

            'salary-component.view',
            'salary-component.create',
            'salary-component.edit',
            'salary-component.delete',

            'skill.view',
            'skill.create',
            'skill.edit',
            'skill.delete',

            'employee.view',
            'employee.create',
            'employee.edit',
            'employee.delete',

            'employee-document.view',
            'employee-document.create',
            'employee-document.edit',
            'employee-document.delete',

            'emergency-contact.view',
            'emergency-contact.create',
            'emergency-contact.edit',
            'emergency-contact.delete',

            'bank-account.view',
            'bank-account.create',
            'bank-account.edit',
            'bank-account.delete',

            'education.view',
            'education.create',
            'education.edit',
            'education.delete',

            'experience.view',
            'experience.create',
            'experience.edit',
            'experience.delete',

            'transfer.view',
            'transfer.create',
            'transfer.edit',
            'transfer.delete',

            'promotion.view',
            'promotion.create',
            'promotion.edit',
            'promotion.delete',

            'resignation.view',
            'resignation.create',
            'resignation.edit',
            'resignation.delete',

            'termination.view',
            'termination.create',
            'termination.edit',
            'termination.delete',

            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',

            'attendance-adjustment.view',
            'attendance-adjustment.create',
            'attendance-adjustment.edit',
            'attendance-adjustment.delete',
            'attendance-adjustment.approve',

            'leave-balance.view',
            'leave-balance.create',
            'leave-balance.edit',
            'leave-balance.delete',
            'leave-balance.generate',
            'leave-balance.encash',

            'leave-request.view',
            'leave-request.create',
            'leave-request.edit',
            'leave-request.delete',
            'leave-request.approve',
            'leave-request.cancel',

            'salary-structure.view',
            'salary-structure.create',
            'salary-structure.edit',
            'salary-structure.delete',

            'salary-template.view',
            'salary-template.create',
            'salary-template.edit',
            'salary-template.delete',
            'salary-template.assign',

            'minimum-wage-rule.view',
            'minimum-wage-rule.create',
            'minimum-wage-rule.edit',
            'minimum-wage-rule.delete',

            'payroll-compliance-report.view',

            'payroll.view',
            'payroll.create',
            'payroll.delete',
            'payroll.mark-paid',

            'expense-claim.view',
            'expense-claim.create',
            'expense-claim.edit',
            'expense-claim.delete',
            'expense-claim.approve',

            'employee-loan.view',
            'employee-loan.create',
            'employee-loan.edit',
            'employee-loan.delete',
            'employee-loan.approve',

            'performance-review.view',
            'performance-review.create',
            'performance-review.edit',
            'performance-review.delete',

            'hr-report.view',
            'leave-report.view',

            'department.view',
            'department.create',
            'department.edit',
            'department.delete',

            'designation.view',
            'designation.create',
            'designation.edit',
            'designation.delete',

            // CRM
            'lead-source.view',
            'lead-source.create',
            'lead-source.edit',
            'lead-source.delete',

            'lead-status.view',
            'lead-status.create',
            'lead-status.edit',
            'lead-status.delete',

            'lead.view',
            'lead.create',
            'lead.edit',
            'lead.delete',

            'contact.view',
            'contact.create',
            'contact.edit',
            'contact.delete',

            'company.view',
            'company.create',
            'company.edit',
            'company.delete',

            'relationship-history.view',
            'relationship-history.create',
            'relationship-history.edit',
            'relationship-history.delete',

            'opportunity.view',
            'opportunity.create',
            'opportunity.edit',
            'opportunity.delete',

            'activity.view',
            'activity.create',
            'activity.edit',
            'activity.delete',

            'follow-up.view',
            'follow-up.create',
            'follow-up.edit',
            'follow-up.delete',

            'email-communication.view',
            'email-communication.create',
            'email-communication.edit',
            'email-communication.delete',

            'quotation.view',
            'quotation.create',
            'quotation.edit',
            'quotation.delete',

            'sales-forecast.view',

            'crm-dashboard.view',
            'crm-report.view',

            // Product Management
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',

            'brand.view',
            'brand.create',
            'brand.edit',
            'brand.delete',

            'unit.view',
            'unit.create',
            'unit.edit',
            'unit.delete',

            'unit-conversion.view',
            'unit-conversion.create',
            'unit-conversion.edit',
            'unit-conversion.delete',

            'product-attribute.view',
            'product-attribute.create',
            'product-attribute.edit',
            'product-attribute.delete',

            'attribute-value.view',
            'attribute-value.create',
            'attribute-value.edit',
            'attribute-value.delete',

            'product.view',
            'product.create',
            'product.edit',
            'product.delete',

            'product-variant.view',
            'product-variant.create',
            'product-variant.edit',
            'product-variant.delete',

            'product-image.view',
            'product-image.create',
            'product-image.edit',
            'product-image.delete',

            'product-bundle.view',
            'product-bundle.create',
            'product-bundle.edit',
            'product-bundle.delete',

            'price-tier.view',
            'price-tier.create',
            'price-tier.edit',
            'price-tier.delete',

            'product-price.view',
            'product-price.create',
            'product-price.edit',
            'product-price.delete',

            'discount-rule.view',
            'discount-rule.create',
            'discount-rule.edit',
            'discount-rule.delete',

            'barcode-generator.view',

            'product-report.view',

            // Sales
            'customer.view',
            'customer.create',
            'customer.edit',
            'customer.delete',

            'customer-group.view',
            'customer-group.create',
            'customer-group.edit',
            'customer-group.delete',

            'payment-term.view',
            'payment-term.create',
            'payment-term.edit',
            'payment-term.delete',

            'price-list.view',
            'price-list.create',
            'price-list.edit',
            'price-list.delete',

            'sales-quotation.view',
            'sales-quotation.create',
            'sales-quotation.edit',
            'sales-quotation.delete',

            'sales-order.view',
            'sales-order.create',
            'sales-order.edit',
            'sales-order.delete',

            'delivery.view',
            'delivery.create',
            'delivery.edit',
            'delivery.delete',

            'delivery-note.view',
            'delivery-note.create',
            'delivery-note.edit',
            'delivery-note.delete',

            'sales-invoice.view',
            'sales-invoice.create',
            'sales-invoice.edit',
            'sales-invoice.delete',

            'credit-note.view',
            'credit-note.create',
            'credit-note.edit',
            'credit-note.delete',

            'sales-return.view',
            'sales-return.create',
            'sales-return.edit',
            'sales-return.delete',

            'pos.view',
            'pos.checkout',
            'pos.void',
            'pos.delete',

            'sales-target.view',
            'sales-target.create',
            'sales-target.edit',
            'sales-target.delete',

            'sales-commission.view',
            'sales-commission.create',
            'sales-commission.edit',
            'sales-commission.delete',
            'sales-commission.mark-paid',

            'sales-report.view',

            // Purchase
            'vendor.view',
            'vendor.create',
            'vendor.edit',
            'vendor.delete',

            'vendor-group.view',
            'vendor-group.create',
            'vendor-group.edit',
            'vendor-group.delete',

            'vendor-performance-review.view',
            'vendor-performance-review.create',
            'vendor-performance-review.edit',
            'vendor-performance-review.delete',

            'purchase-request.view',
            'purchase-request.create',
            'purchase-request.edit',
            'purchase-request.delete',
            'purchase-request.approve',

            'rfq.view',
            'rfq.create',
            'rfq.edit',
            'rfq.delete',

            'supplier-quotation.view',
            'supplier-quotation.create',
            'supplier-quotation.edit',
            'supplier-quotation.delete',

            'purchase-order.view',
            'purchase-order.create',
            'purchase-order.edit',
            'purchase-order.delete',

            'goods-receipt.view',
            'goods-receipt.create',
            'goods-receipt.edit',
            'goods-receipt.delete',

            'purchase-invoice.view',
            'purchase-invoice.create',
            'purchase-invoice.edit',
            'purchase-invoice.delete',

            'debit-note.view',
            'debit-note.create',
            'debit-note.edit',
            'debit-note.delete',

            'purchase-return.view',
            'purchase-return.create',
            'purchase-return.edit',
            'purchase-return.delete',

            'purchase-report.view',

            // Inventory
            'warehouse.view',
            'warehouse.create',
            'warehouse.edit',
            'warehouse.delete',

            'warehouse-location.view',
            'warehouse-location.create',
            'warehouse-location.edit',
            'warehouse-location.delete',

            'stock-entry.view',
            'stock-entry.create',
            'stock-entry.edit',
            'stock-entry.delete',

            'opening-stock.view',
            'opening-stock.create',
            'opening-stock.edit',
            'opening-stock.delete',

            'stock-adjustment.view',
            'stock-adjustment.create',
            'stock-adjustment.edit',
            'stock-adjustment.delete',

            'stock-transfer.view',
            'stock-transfer.create',
            'stock-transfer.edit',
            'stock-transfer.delete',

            'stock-count.view',
            'stock-count.create',
            'stock-count.edit',
            'stock-count.delete',

            'product-batch.view',
            'product-batch.create',
            'product-batch.edit',
            'product-batch.delete',

            'product-serial.view',
            'product-serial.create',
            'product-serial.edit',
            'product-serial.delete',

            'inventory-transaction.view',

            'stock-valuation.view',

            'reorder-level.view',
            'reorder-level.create',
            'reorder-level.edit',
            'reorder-level.delete',

            'low-stock-alert.view',

            'inventory-report.view',

            // Accounting
            'chart-of-account.view',
            'chart-of-account.create',
            'chart-of-account.edit',
            'chart-of-account.delete',

            'account-type.view',
            'account-type.create',
            'account-type.edit',
            'account-type.delete',

            'journal-entry.view',
            'journal-entry.create',
            'journal-entry.edit',
            'journal-entry.delete',

            'general-ledger.view',

            'cash-book.view',

            'finance-bank-account.view',
            'finance-bank-account.create',
            'finance-bank-account.edit',
            'finance-bank-account.delete',

            'bank-transaction.view',
            'bank-transaction.create',
            'bank-transaction.edit',
            'bank-transaction.delete',

            'bank-reconciliation.view',
            'bank-reconciliation.create',
            'bank-reconciliation.edit',
            'bank-reconciliation.delete',

            'accounts-receivable.view',

            'accounts-payable.view',

            'payment-receive.view',
            'payment-receive.create',
            'payment-receive.edit',
            'payment-receive.delete',

            'payment-make.view',
            'payment-make.create',
            'payment-make.edit',
            'payment-make.delete',

            'trial-balance.view',
            'profit-and-loss.view',
            'balance-sheet.view',
            'cash-flow.view',

            'tax-rate.view',
            'tax-rate.create',
            'tax-rate.edit',
            'tax-rate.delete',

            'financial-report.view',

            // Asset Management
            'asset-category.view',
            'asset-category.create',
            'asset-category.edit',
            'asset-category.delete',

            'asset.view',
            'asset.create',
            'asset.edit',
            'asset.delete',

            'asset-purchase.view',
            'asset-purchase.create',
            'asset-purchase.edit',
            'asset-purchase.delete',

            'asset-assignment.view',
            'asset-assignment.create',
            'asset-assignment.edit',
            'asset-assignment.delete',

            'employee-asset-tracking.view',

            'asset-location.view',
            'asset-location.create',
            'asset-location.edit',
            'asset-location.delete',

            'asset-location-movement.view',
            'asset-location-movement.create',
            'asset-location-movement.edit',
            'asset-location-movement.delete',

            'asset-maintenance-schedule.view',
            'asset-maintenance-schedule.create',
            'asset-maintenance-schedule.edit',
            'asset-maintenance-schedule.delete',

            'asset-maintenance-record.view',
            'asset-maintenance-record.create',
            'asset-maintenance-record.edit',
            'asset-maintenance-record.delete',

            'asset-depreciation.view',

            'asset-disposal.view',
            'asset-disposal.create',
            'asset-disposal.edit',
            'asset-disposal.delete',

            'asset-report.view',

            // Project Management
            'client.view',
            'client.create',
            'client.edit',
            'client.delete',

            'project.view',
            'project.create',
            'project.edit',
            'project.delete',

            'project-budget.view',
            'project-budget.create',
            'project-budget.edit',
            'project-budget.delete',

            'project-team-member.view',
            'project-team-member.create',
            'project-team-member.edit',
            'project-team-member.delete',

            'project-milestone.view',
            'project-milestone.create',
            'project-milestone.edit',
            'project-milestone.delete',

            'project-task.view',
            'project-task.create',
            'project-task.edit',
            'project-task.delete',

            'task-board.view',
            'task-board.move',

            'gantt-chart.view',

            'project-task-dependency.view',
            'project-task-dependency.create',
            'project-task-dependency.edit',
            'project-task-dependency.delete',

            'project-time-entry.view',
            'project-time-entry.create',
            'project-time-entry.edit',
            'project-time-entry.delete',
            'project-time-entry.track',

            'project-timesheet.view',
            'project-timesheet.create',
            'project-timesheet.edit',
            'project-timesheet.delete',
            'project-timesheet.submit',
            'project-timesheet.review',

            'project-expense.view',
            'project-expense.create',
            'project-expense.edit',
            'project-expense.delete',
            'project-expense.review',

            'project-invoice.view',
            'project-invoice.create',
            'project-invoice.edit',
            'project-invoice.delete',
            'project-invoice.bill',

            'project-report.view',

            // Support
            'ticket-category.view',
            'ticket-category.create',
            'ticket-category.edit',
            'ticket-category.delete',

            'ticket.view',
            'ticket.create',
            'ticket.edit',
            'ticket.delete',
            'ticket.record-response',
            'ticket.escalate',

            'ticket-assignment.view',
            'ticket-assignment.create',
            'ticket-assignment.edit',
            'ticket-assignment.delete',

            'ticket-status.view',
            'ticket-status.create',
            'ticket-status.edit',
            'ticket-status.delete',

            'ticket-priority.view',
            'ticket-priority.create',
            'ticket-priority.edit',
            'ticket-priority.delete',

            'sla-policy.view',
            'sla-policy.create',
            'sla-policy.edit',
            'sla-policy.delete',

            'escalation-rule.view',
            'escalation-rule.create',
            'escalation-rule.edit',
            'escalation-rule.delete',

            'ticket-escalation.view',

            'knowledge-base-category.view',
            'knowledge-base-category.create',
            'knowledge-base-category.edit',
            'knowledge-base-category.delete',

            'knowledge-base-article.view',
            'knowledge-base-article.create',
            'knowledge-base-article.edit',
            'knowledge-base-article.delete',

            'support-report.view',

            // Budget & Finance
            'budget.view',
            'budget.create',
            'budget.edit',
            'budget.delete',

            'department-budget.view',
            'department-budget.create',
            'department-budget.edit',
            'department-budget.delete',

            'finance-project-budget.view',
            'finance-project-budget.create',
            'finance-project-budget.edit',
            'finance-project-budget.delete',

            'activity-log.view',
            'activity-log.details'
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'admin'
            ]);
        }

        $adminRole = Role::create([
            'name' => 'Super Admin',
            'guard_name' => 'admin'
        ]);

        $adminRole->givePermissionTo(Permission::all());
    }
}
