<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Users follow organizations to read the commits that mention them in their Following log. The primary key finds
     * the organizations a user follows, and the other index the followers of an organization.
     */
    public function up(): void
    {
        Schema::create('organization_follows', function (Blueprint $table): void {
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'organization_id']);
            $table->index('organization_id');
        });
    }
};
