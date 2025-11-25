<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password_hash');
            $t->enum('role', ['admin', 'designer', 'copywriter', 'web_designer']);
            $t->timestamps();
        });

        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $t->enum('status', ['active', 'completed', 'archived'])->default('active');
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo');
            $t->date('due_date')->nullable();
            $t->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $t->timestamps();
        });

        Schema::create('project_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('filename');
            $t->text('url');
            $t->timestamp('uploaded_at')->useCurrent();
            $t->timestamps();
        });

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('content');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->date('issue_date')->nullable();
            $t->date('due_date')->nullable();
            $t->decimal('total_amount', 12, 2)->nullable();
            $t->decimal('tax', 12, 2)->nullable();
            $t->decimal('discount', 12, 2)->nullable();
            $t->enum('status', ['draft', 'sent', 'paid', 'overdue'])->default('draft');
            $t->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description');
            $t->integer('quantity')->default(1);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('total_price', 12, 2);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->timestamp('paid_at')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->string('method')->nullable();
            $t->enum('payment_status', ['pending', 'confirmed'])->default('pending');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        $tables = [
            'payments',
            'invoice_items',
            'invoices',
            'comments',
            'project_files',
            'tasks',
            'projects',
            'users',
        ];
        foreach ($tables as $t) Schema::dropIfExists($t);
    }
};
