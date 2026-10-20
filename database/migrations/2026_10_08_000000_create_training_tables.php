<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();            // training.ilitech-engineering.dz/{slug}
            $t->json('title');                       // {ar,fr,en}
            $t->json('content')->nullable();         // {ar:[blocks],fr:[...],en:[...]}  (edited in admin)
            $t->json('seo')->nullable();             // {title:{..},description:{..}}
            $t->json('addons')->nullable();          // [{key,label:{..},price}]
            $t->json('contact')->nullable();         // {whatsapp,ccp_number,ccp_name,rip,group}
            $t->unsignedTinyInteger('weeks')->default(6);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('course_sessions', function (Blueprint $t) {   // a dated "cohort"
            $t->id();
            $t->foreignId('course_id')->constrained()->cascadeOnDelete();
            $t->dateTime('starts_at');
            $t->enum('mode', ['online', 'offline', 'hybrid'])->default('online');
            $t->string('location')->nullable();
            $t->string('meeting_url')->nullable();
            $t->unsignedInteger('seats')->default(20);
            $t->unsignedInteger('price')->default(0);         // 0 = free
            $t->unsignedInteger('early_price')->nullable();
            $t->unsignedInteger('early_seats')->default(0);
            $t->enum('status', ['open', 'closed'])->default('open');
            $t->timestamps();
        });
        Schema::create('meetings', function (Blueprint $t) {          // each weekly class
            $t->id();
            $t->foreignId('course_session_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('n');
            $t->dateTime('starts_at');
            $t->string('topic')->nullable();
            $t->string('meeting_url')->nullable();
            $t->timestamps();
        });
        Schema::create('registrations', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            // T-01: historical registrations must never disappear with their course/session -> RESTRICT
            $t->foreignId('course_id')->constrained()->restrictOnDelete();
            $t->foreignId('course_session_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->char('locale', 2)->default('ar');
            $t->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $t->enum('method', ['free', 'cib', 'ccp', 'cash'])->default('free');
            $t->unsignedInteger('amount')->default(0);
            $t->json('addons')->nullable();
            $t->boolean('consent')->default(false);           // marketing consent
            $t->string('invoice_id')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('confirmed_at')->nullable();        // T-01.1: first confirmation, set once, never cleared
            $t->timestamps();
            $t->index(['phone', 'course_session_id']);
        });
        Schema::create('automations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();  // null = all courses
            $t->enum('anchor', ['registration', 'paid', 'meeting']);
            $t->integer('offset_minutes')->default(0);        // negative = before the anchor
            $t->enum('audience', ['all', 'free', 'paid'])->default('all');
            $t->json('channels');                              // ["mail","whatsapp","sms"]
            $t->boolean('is_promo')->default(false);           // needs marketing consent
            $t->foreignId('promote_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $t->json('subject')->nullable();
            $t->json('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('message_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $t->foreignId('automation_id')->constrained()->restrictOnDelete();   // T-01: an automation with history can only be deactivated
            $t->unsignedBigInteger('meeting_id')->default(0);
            $t->string('channel');
            $t->string('status')->default('queued');
            $t->text('error')->nullable();
            $t->timestamps();
            $t->unique(['registration_id', 'automation_id', 'meeting_id', 'channel']);  // never send twice
        });
    }
    public function down(): void
    {
        foreach (['message_logs', 'automations', 'registrations', 'meetings', 'course_sessions', 'courses'] as $t) Schema::dropIfExists($t);
    }
};
