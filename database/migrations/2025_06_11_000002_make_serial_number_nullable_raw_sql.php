<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Use raw SQL to modify the column without requiring doctrine/dbal
        DB::statement('ALTER TABLE sale_items MODIFY COLUMN serial_number VARCHAR(255) NULL');
    }

    public function down()
    {
        // Revert the change - make it NOT NULL again
        DB::statement('ALTER TABLE sale_items MODIFY COLUMN serial_number VARCHAR(255) NOT NULL');
    }
};
