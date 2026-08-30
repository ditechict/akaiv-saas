<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 120)->unique();
            $table->string('registration_number', 255)->nullable();
            $table->string('court_type', 100)->nullable()->comment('High Court / Magistrate / Customary Court of Appeal / Tribunal / Law Firm');
            $table->string('jurisdiction_state', 100)->nullable();
            $table->string('contact_email', 255);
            $table->string('contact_phone', 50)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('plan', 40)->default('free')->comment('free, pro, enterprise');
            $table->unsignedBigInteger('storage_quota_bytes')->default(536870912);
            $table->unsignedBigInteger('storage_used_bytes')->default(0);
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['plan', 'is_suspended']);
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 60)->default('member')->comment('owner, billing_admin, workspace_manager, member_write, member_read, auditor');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['organization_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 120);
            $table->string('description', 500)->nullable();
            $table->string('default_permission', 40)->default('private')->comment('private, org_read, org_write');
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['organization_id', 'slug']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('case_number', 150)->unique()->nullable();
            $table->string('suit_number', 150)->unique()->nullable();
            $table->string('title', 500);
            $table->string('parties_json')->nullable()->comment('JSON array of {role: Claimant/Defendant, name: "..."}');
            $table->string('court_name', 255)->nullable();
            $table->string('bench_judge_name', 255)->nullable();
            $table->string('jurisdiction', 150)->nullable();
            $table->text('notes')->nullable();
            $table->date('date_filed')->nullable();
            $table->date('date_judgment')->nullable();
            $table->string('status', 60)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->string('name', 255);
            $table->unsignedInteger('depth')->default(0);
            $table->string('path_cache', 1000)->nullable();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['organization_id', 'workspace_id', 'parent_folder_id', 'name'], 'org_ws_parent_name_unique');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('color', 15)->default('#3b82f6');
            $table->boolean('is_system')->default(false);
            $table->unique(['organization_id', 'name']);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('friendly_name', 500);
            $table->string('original_filename', 500);
            $table->string('slug', 150);
            $table->string('storage_disk', 40)->default('s3');
            $table->string('storage_path', 1000);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('mime_type', 200)->nullable();
            $table->string('file_extension', 30)->nullable();
            $table->string('sha256_checksum', 64)->nullable();
            $table->string('folio_number', 200)->nullable();
            $table->longText('description')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 60)->default('uploading')->comment('uploading, quarantined, draft, published, archived, deleted');
            $table->boolean('ocr_required')->default(true);
            $table->boolean('ocr_completed')->default(false);
            $table->boolean('virus_scanned')->default(false);
            $table->boolean('virus_found')->default(false);
            $table->timestamp('virus_scanned_at')->nullable();
            $table->date('retention_date')->nullable();
            $table->string('retention_policy', 60)->nullable();
            $table->unsignedBigInteger('download_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->unique(['organization_id', 'folder_id', 'friendly_name']);
            $table->index(['organization_id', 'status']);
            $table->index(['sha256_checksum', 'organization_id']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('storage_path', 1000);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256_checksum', 64)->nullable();
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['document_id', 'version_number']);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150);
            $table->string('color', 15)->default('#6366f1');
            $table->boolean('is_system')->default(false);
            $table->unique(['organization_id', 'slug']);
            $table->timestamps();
        });

        Schema::create('document_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('auto_tagged')->default(false);
            $table->unique(['document_id', 'tag_id']);
            $table->timestamps();
        });

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->ulid('token')->unique();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email', 255)->nullable();
            $table->string('allowed_ips_csv', 1000)->nullable();
            $table->string('password_hash', 255)->nullable();
            $table->unsignedInteger('max_accesses')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->boolean('can_download')->default(true);
            $table->boolean('can_preview')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name', 255)->nullable();
            $table->text('description');
            $table->string('subject_type', 255)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type', 255)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties')->nullable();
            $table->char('batch_uuid', 36)->nullable();
            $table->string('event', 100)->nullable();
            $table->index(['log_name', 'subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['organization_id', 'created_at']);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id', 255)->unique()->nullable();
            $table->string('stripe_status', 60);
            $table->string('stripe_price', 255)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'stripe_status']);
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id', 255)->unique();
            $table->string('stripe_product', 255)->nullable();
            $table->string('stripe_price', 255);
            $table->unsignedInteger('quantity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('shares');
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
