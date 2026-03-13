<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, recreate with new enum
            DB::statement('CREATE TABLE submissions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL REFERENCES tenants(id),
                document_id INTEGER NOT NULL REFERENCES documents(id),
                recipient_name VARCHAR NOT NULL,
                recipient_email VARCHAR NOT NULL,
                status VARCHAR CHECK(status IN (\'draft\', \'sent\', \'pending\', \'questions\', \'processing\', \'signed\')) DEFAULT \'draft\',
                token VARCHAR UNIQUE NOT NULL,
                ip_address VARCHAR,
                user_agent VARCHAR,
                sent_at TIMESTAMP,
                viewed_at TIMESTAMP,
                signed_at TIMESTAMP,
                expires_at TIMESTAMP,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )');
            DB::statement('INSERT INTO submissions_new SELECT * FROM submissions');
            DB::statement('DROP TABLE submissions');
            DB::statement('ALTER TABLE submissions_new RENAME TO submissions');
        } else {
            DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_status_check');
            DB::statement("ALTER TABLE submissions ADD CONSTRAINT submissions_status_check CHECK (status IN ('draft', 'sent', 'pending', 'questions', 'processing', 'signed'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TABLE submissions_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id INTEGER NOT NULL REFERENCES tenants(id),
                document_id INTEGER NOT NULL REFERENCES documents(id),
                recipient_name VARCHAR NOT NULL,
                recipient_email VARCHAR NOT NULL,
                status VARCHAR CHECK(status IN (\'draft\', \'sent\', \'pending\', \'questions\', \'signed\')) DEFAULT \'draft\',
                token VARCHAR UNIQUE NOT NULL,
                ip_address VARCHAR,
                user_agent VARCHAR,
                sent_at TIMESTAMP,
                viewed_at TIMESTAMP,
                signed_at TIMESTAMP,
                expires_at TIMESTAMP,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )');
            DB::statement("DELETE FROM submissions WHERE status = 'processing'");
            DB::statement('INSERT INTO submissions_new SELECT * FROM submissions');
            DB::statement('DROP TABLE submissions');
            DB::statement('ALTER TABLE submissions_new RENAME TO submissions');
        } else {
            DB::statement('ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_status_check');
            DB::statement("ALTER TABLE submissions ADD CONSTRAINT submissions_status_check CHECK (status IN ('draft', 'sent', 'pending', 'questions', 'signed'))");
        }
    }
};
