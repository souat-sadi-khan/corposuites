<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `worked_minutes` becomes the day's TOTAL worked time, accumulated across
 * every closed check-in/check-out SESSION that day (see
 * 2026_09_07_090000_create_attendance_punches_table) — incremented once per
 * check-out rather than recomputed from a single check_in/check_out pair on
 * read, so AttendanceReportService::workedMinutes() can just read this
 * column directly regardless of how many sessions happened.
 *
 * Every EXISTING row only ever had one check_in/check_out pair (multi-
 * session didn't exist yet), so this migration backfills worked_minutes
 * for all of them using the exact same single-pair, overnight-aware
 * calculation AttendanceReportService::workedMinutes() already used —
 * otherwise every already-recorded day's worked hours would silently drop
 * to zero in every past report/export the moment this ships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('worked_minutes')->default(0)->after('overtime_hours');
        });

        DB::table('attendances')
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $date = $row->attendance_date;
                    $in = strtotime($date . ' ' . $row->check_in);
                    $out = strtotime($date . ' ' . $row->check_out);

                    if ($out <= $in) {
                        $out += 86400; // overnight shift, same rule as workedMinutes()
                    }

                    $minutes = (int) round(($out - $in) / 60);

                    DB::table('attendances')->where('id', $row->id)->update(['worked_minutes' => max(0, $minutes)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('worked_minutes');
        });
    }
};
