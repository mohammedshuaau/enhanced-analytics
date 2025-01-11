<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enhanced_analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('page_url');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('referrer_url')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('visitor_id')->nullable();
            $table->boolean('is_new_visitor')->default(false);
            $table->boolean('is_new_day_visit')->default(false);
            $table->boolean('is_new_hour_visit')->default(false);
            $table->boolean('is_new_page_visit')->default(false);
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['visited_at']);
            $table->index(['country_code']);
            $table->index(['device_type']);
            $table->index(['session_id']);
            $table->index(['visitor_id']);
            $table->index(['is_new_visitor']);
            $table->index(['is_new_day_visit']);
        });

        Schema::create('enhanced_analytics_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // daily, weekly, monthly
            $table->date('date');
            $table->string('dimension'); // country, device, browser, etc.
            $table->string('dimension_value');
            $table->integer('total_visits')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('unique_page_views')->default(0);
            $table->integer('returning_visitors')->default(0);
            $table->timestamps();

            $table->unique(['type', 'date', 'dimension', 'dimension_value'], 'analytics_aggregates_unique');
            $table->index(['type', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('enhanced_analytics_page_views');
        Schema::dropIfExists('enhanced_analytics_aggregates');
    }
}; 