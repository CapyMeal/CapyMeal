<?php

namespace Tests\Unit\Support;

use App\Support\DiaryTimeline;
use PHPUnit\Framework\TestCase;

class DiaryTimelineTest extends TestCase
{
    private function entries(string ...$dates)
    {
        return collect($dates)->map(fn ($date) => (object) ['date' => $date]);
    }

    /** Resume la secuencia como texto para comparar fácil. */
    private function summary(array $items): array
    {
        return array_map(fn ($item) => $item['type'] === 'entry'
            ? $item['entry']->date
            : 'gap '.$item['from']->toDateString().'..'.$item['to']->toDateString(), $items);
    }

    public function test_without_entries_returns_nothing(): void
    {
        $this->assertSame([], DiaryTimeline::build(collect(), '2026-11-01', '2026-11-30'));
    }

    public function test_consecutive_days_have_no_gaps(): void
    {
        $items = DiaryTimeline::build($this->entries('2026-11-10', '2026-11-11'), null, null);

        $this->assertSame(['2026-11-10', '2026-11-11'], $this->summary($items));
    }

    public function test_missing_days_between_entries_collapse_into_one_gap(): void
    {
        $items = DiaryTimeline::build($this->entries('2026-11-10', '2026-11-14'), null, null);

        $this->assertSame(['2026-11-10', 'gap 2026-11-11..2026-11-13', '2026-11-14'], $this->summary($items));
    }

    public function test_chosen_range_shows_gaps_at_both_ends(): void
    {
        $items = DiaryTimeline::build($this->entries('2026-11-10'), '2026-11-08', '2026-11-12');

        $this->assertSame(['gap 2026-11-08..2026-11-09', '2026-11-10', 'gap 2026-11-11..2026-11-12'], $this->summary($items));
    }

    public function test_without_end_date_does_not_invent_a_gap_until_today(): void
    {
        $items = DiaryTimeline::build($this->entries('2026-11-10'), '2026-11-10', null);

        $this->assertSame(['2026-11-10'], $this->summary($items));
    }
}
