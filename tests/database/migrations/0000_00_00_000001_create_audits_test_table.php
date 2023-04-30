<?php
include_once __DIR__.'/../../../database/migrations/audits.stub';

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTestTable extends CreateAuditsTable
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        parent::up();

        Schema::table(config('audit.drivers.database.table', 'audits'), function(Blueprint $table) {
            $table->unsignedInteger('tenant_id')->nullable();
        });
    }
}
