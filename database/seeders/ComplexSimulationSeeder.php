<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use App\Models\Workflow;
use App\Models\FlowObject;
use App\Models\ObjectConnection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComplexSimulationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a complex financial simulation scenario with:
     * - Multiple income sources (salary, freelance, investments, side gigs)
     * - Comprehensive expense categories (housing, food, utilities, entertainment, etc.)
     * - Split and priority rules for intelligent money routing
     * - Tax calculations and deductions
     * - Recurring and one-time expenses
     */
    public function run(): void
    {
        // Create comprehensive categories
        $this->createCategories();

        // Create a complex workflow
        $this->createComplexWorkflow();
    }

    private function createCategories(): void
    {
        $categories = [
            // ─── INCOME CATEGORIES ───────────────────────────────────────
            ['name' => 'Salary (Primary)', 'type' => 'income', 'amount' => 8000000],
            ['name' => 'Freelance Projects', 'type' => 'income', 'amount' => 3000000],
            ['name' => 'Investment Returns', 'type' => 'income', 'amount' => 1500000],
            ['name' => 'Side Gig (Tutoring)', 'type' => 'income', 'amount' => 1200000],
            ['name' => 'Bonus (Annual)', 'type' => 'income', 'amount' => 5000000],
            ['name' => 'Rental Income', 'type' => 'income', 'amount' => 2000000],

            // ─── ESSENTIAL EXPENSES ───────────────────────────────────────
            ['name' => 'Rent/Mortgage', 'type' => 'expense', 'amount' => 2500000],
            ['name' => 'Utilities (Electric, Water, Gas)', 'type' => 'expense', 'amount' => 400000],
            ['name' => 'Internet & Phone', 'type' => 'expense', 'amount' => 200000],
            ['name' => 'Groceries', 'type' => 'expense', 'amount' => 1000000],
            ['name' => 'Car Payment', 'type' => 'expense', 'amount' => 600000],
            ['name' => 'Car Insurance', 'type' => 'expense', 'amount' => 250000],
            ['name' => 'Gas/Fuel', 'type' => 'expense', 'amount' => 350000],
            ['name' => 'Health Insurance', 'type' => 'expense', 'amount' => 500000],
            ['name' => 'Medical/Dental', 'type' => 'expense', 'amount' => 300000],

            // ─── LIFESTYLE EXPENSES ───────────────────────────────────────
            ['name' => 'Dining Out', 'type' => 'expense', 'amount' => 700000],
            ['name' => 'Entertainment (Movies, Games)', 'type' => 'expense', 'amount' => 300000],
            ['name' => 'Gym Membership', 'type' => 'expense', 'amount' => 100000],
            ['name' => 'Subscriptions (Netflix, Spotify, etc)', 'type' => 'expense', 'amount' => 150000],
            ['name' => 'Shopping/Clothing', 'type' => 'expense', 'amount' => 400000],
            ['name' => 'Personal Care', 'type' => 'expense', 'amount' => 200000],

            // ─── DEBT & SAVINGS ───────────────────────────────────────────
            ['name' => 'Credit Card Payment', 'type' => 'expense', 'amount' => 500000],
            ['name' => 'Student Loan Payment', 'type' => 'expense', 'amount' => 400000],
            ['name' => 'Emergency Fund Contribution', 'type' => 'expense', 'amount' => 800000],
            ['name' => 'Retirement Contribution', 'type' => 'expense', 'amount' => 700000],

            // ─── IRREGULAR/SEASONAL EXPENSES ───────────────────────────────
            ['name' => 'Car Maintenance', 'type' => 'expense', 'amount' => 350000],
            ['name' => 'Home Maintenance', 'type' => 'expense', 'amount' => 500000],
            ['name' => 'Gifts & Holidays', 'type' => 'expense', 'amount' => 400000],
            ['name' => 'Travel/Vacation', 'type' => 'expense', 'amount' => 1000000],
            ['name' => 'Professional Development', 'type' => 'expense', 'amount' => 300000],
            ['name' => 'Pet Care', 'type' => 'expense', 'amount' => 200000],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                ['type' => $cat['type'], 'amount' => $cat['amount']]
            );
        }
    }

    private function createComplexWorkflow(): void
    {
        // Create a project
        $project = Project::firstOrCreate(
            ['name' => 'Personal Finance Simulation'],
            ['user_id' => 1]
        );

        // Create workflow
        $workflow = Workflow::create([
            'project_id' => $project->id,
            'name' => 'Complex Financial Flow',
            'viewport_json' => ['x' => 0, 'y' => 0, 'zoom' => 1],
        ]);

        // ─── CREATE INCOME NODES ───────────────────────────────────────────
        $salary = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'income',
            'name' => 'Salary (Primary)',
            'data_json' => [
                'amount' => 8000000,
                'frequency' => 'monthly',
                'tax_rate' => 20,
                'start_delay' => 0,
            ],
            'position_x' => 50,
            'position_y' => 100,
        ]);

        $freelance = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'income',
            'name' => 'Freelance Projects',
            'data_json' => [
                'amount' => 3000000,
                'frequency' => 'monthly',
                'tax_rate' => 25,
                'start_delay' => 0,
            ],
            'position_x' => 50,
            'position_y' => 200,
        ]);

        $investments = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'income',
            'name' => 'Investment Returns',
            'data_json' => [
                'amount' => 1500000,
                'frequency' => 'monthly',
                'tax_rate' => 15,
                'start_delay' => 0,
            ],
            'position_x' => 50,
            'position_y' => 300,
        ]);

        $sideGig = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'income',
            'name' => 'Side Gig (Tutoring)',
            'data_json' => [
                'amount' => 1200000,
                'frequency' => 'weekly',
                'tax_rate' => 20,
                'start_delay' => 0,
            ],
            'position_x' => 50,
            'position_y' => 400,
        ]);

        $rental = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'income',
            'name' => 'Rental Income',
            'data_json' => [
                'amount' => 2000000,
                'frequency' => 'monthly',
                'tax_rate' => 30,
                'start_delay' => 0,
            ],
            'position_x' => 50,
            'position_y' => 500,
        ]);

        // ─── CREATE SPLIT RULE (Main Income Router) ───────────────────────
        $mainSplitRule = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'rule',
            'name' => 'Income Distribution',
            'data_json' => [
                'rule_type' => 'split',
            ],
            'position_x' => 300,
            'position_y' => 250,
        ]);

        // ─── CREATE SECONDARY SPLIT RULES ───────────────────────────────────
        $essentialRule = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'rule',
            'name' => 'Essential Expenses',
            'data_json' => [
                'rule_type' => 'split',
            ],
            'position_x' => 550,
            'position_y' => 100,
        ]);

        $lifestyleRule = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'rule',
            'name' => 'Lifestyle & Entertainment',
            'data_json' => [
                'rule_type' => 'split',
            ],
            'position_x' => 550,
            'position_y' => 300,
        ]);

        $savingsRule = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'rule',
            'name' => 'Savings & Investments',
            'data_json' => [
                'rule_type' => 'split',
            ],
            'position_x' => 550,
            'position_y' => 500,
        ]);

        $debtRule = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'rule',
            'name' => 'Debt Payments',
            'data_json' => [
                'rule_type' => 'priority',
            ],
            'position_x' => 550,
            'position_y' => 700,
        ]);

        // ─── CREATE EXPENSE NODES (ESSENTIAL) ───────────────────────────────
        $housing = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Rent/Mortgage',
            'data_json' => [
                'amount' => 2500000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 50,
        ]);

        $utilities = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Utilities',
            'data_json' => [
                'amount' => 400000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 120,
        ]);

        $internet = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Internet & Phone',
            'data_json' => [
                'amount' => 200000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 190,
        ]);

        $groceries = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Groceries',
            'data_json' => [
                'amount' => 1000000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 260,
        ]);

        $carPayment = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Car Payment',
            'data_json' => [
                'amount' => 600000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 330,
        ]);

        $carInsurance = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Car Insurance',
            'data_json' => [
                'amount' => 250000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 400,
        ]);

        $gas = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Gas/Fuel',
            'data_json' => [
                'amount' => 350000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 470,
        ]);

        $healthInsurance = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Health Insurance',
            'data_json' => [
                'amount' => 500000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 540,
        ]);

        // ─── CREATE EXPENSE NODES (LIFESTYLE) ───────────────────────────────
        $dining = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Dining Out',
            'data_json' => [
                'amount' => 700000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 650,
        ]);

        $entertainment = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Entertainment',
            'data_json' => [
                'amount' => 300000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 720,
        ]);

        $gym = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Gym Membership',
            'data_json' => [
                'amount' => 100000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 790,
        ]);

        $subscriptions = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Subscriptions',
            'data_json' => [
                'amount' => 150000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 860,
        ]);

        $shopping = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Shopping/Clothing',
            'data_json' => [
                'amount' => 400000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 930,
        ]);

        // ─── CREATE EXPENSE NODES (SAVINGS & DEBT) ───────────────────────────
        $emergency = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Emergency Fund',
            'data_json' => [
                'amount' => 800000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 1050,
        ]);

        $retirement = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Retirement Contribution',
            'data_json' => [
                'amount' => 700000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 1120,
        ]);

        $creditCard = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Credit Card Payment',
            'data_json' => [
                'amount' => 500000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 1190,
        ]);

        $studentLoan = FlowObject::create([
            'workflow_id' => $workflow->id,
            'type' => 'outcome',
            'name' => 'Student Loan Payment',
            'data_json' => [
                'amount' => 400000,
                'frequency' => 'monthly',
            ],
            'position_x' => 800,
            'position_y' => 1260,
        ]);

        // ─── CREATE CONNECTIONS (Income → Main Rule) ───────────────────────
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $salary->id,
            'target_object_id' => $mainSplitRule->id,
            'edge_data' => ['percentage' => 100],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $freelance->id,
            'target_object_id' => $mainSplitRule->id,
            'edge_data' => ['percentage' => 100],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $investments->id,
            'target_object_id' => $mainSplitRule->id,
            'edge_data' => ['percentage' => 100],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $sideGig->id,
            'target_object_id' => $mainSplitRule->id,
            'edge_data' => ['percentage' => 100],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $rental->id,
            'target_object_id' => $mainSplitRule->id,
            'edge_data' => ['percentage' => 100],
        ]);

        // ─── CREATE CONNECTIONS (Main Rule → Secondary Rules) ───────────────
        // 60% to essential expenses
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $mainSplitRule->id,
            'target_object_id' => $essentialRule->id,
            'edge_data' => ['percentage' => 60],
        ]);

        // 20% to lifestyle
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $mainSplitRule->id,
            'target_object_id' => $lifestyleRule->id,
            'edge_data' => ['percentage' => 15],
        ]);

        // 15% to savings
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $mainSplitRule->id,
            'target_object_id' => $savingsRule->id,
            'edge_data' => ['percentage' => 15],
        ]);

        // 10% to debt (priority)
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $mainSplitRule->id,
            'target_object_id' => $debtRule->id,
            'edge_data' => ['percentage' => 10],
        ]);

        // ─── CREATE CONNECTIONS (Essential Rule → Expenses) ────────────────
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $housing->id,
            'edge_data' => ['percentage' => 40],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $utilities->id,
            'edge_data' => ['percentage' => 8],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $internet->id,
            'edge_data' => ['percentage' => 4],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $groceries->id,
            'edge_data' => ['percentage' => 20],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $carPayment->id,
            'edge_data' => ['percentage' => 12],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $carInsurance->id,
            'edge_data' => ['percentage' => 5],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $gas->id,
            'edge_data' => ['percentage' => 7],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $essentialRule->id,
            'target_object_id' => $healthInsurance->id,
            'edge_data' => ['percentage' => 4],
        ]);

        // ─── CREATE CONNECTIONS (Lifestyle Rule → Expenses) ────────────────
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $lifestyleRule->id,
            'target_object_id' => $dining->id,
            'edge_data' => ['percentage' => 50],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $lifestyleRule->id,
            'target_object_id' => $entertainment->id,
            'edge_data' => ['percentage' => 20],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $lifestyleRule->id,
            'target_object_id' => $gym->id,
            'edge_data' => ['percentage' => 8],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $lifestyleRule->id,
            'target_object_id' => $subscriptions->id,
            'edge_data' => ['percentage' => 12],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $lifestyleRule->id,
            'target_object_id' => $shopping->id,
            'edge_data' => ['percentage' => 10],
        ]);

        // ─── CREATE CONNECTIONS (Savings Rule → Expenses) ────────────────
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $savingsRule->id,
            'target_object_id' => $emergency->id,
            'edge_data' => ['percentage' => 55],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $savingsRule->id,
            'target_object_id' => $retirement->id,
            'edge_data' => ['percentage' => 45],
        ]);

        // ─── CREATE CONNECTIONS (Debt Rule → Expenses) ────────────────────
        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $debtRule->id,
            'target_object_id' => $creditCard->id,
            'edge_data' => ['percentage' => 55],
        ]);

        ObjectConnection::create([
            'workflow_id' => $workflow->id,
            'source_object_id' => $debtRule->id,
            'target_object_id' => $studentLoan->id,
            'edge_data' => ['percentage' => 45],
        ]);
    }
}
