<?php

namespace App\View\Components;

use Closure;
use DateTime;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use IntlDateFormatter;

class DateTimeDisplay extends Component
{
    public ?string $date;
    public ?string $time;
    public bool $showDate;
    public bool $showTime;
    public bool $showYear;
    public bool $is24h;

    public function __construct(
        string $date = null,
        string $time = null,
        bool $showDate = true,
        bool $showTime = true,
        bool $showYear = true,
        bool $is24h = true,
        public string $class = '',
    ) {
        $this->date = $date;
        $this->time = $time;
        $this->showDate = $showDate;
        $this->showTime = $showTime;
        $this->showYear = $showYear;
        $this->is24h = $is24h;
    }

    public function formatted(): ?string
    {
        if (! $this->date && ! $this->time) {
            return null;
        }

        $locale = app()->getLocale();
        $pattern = $this->getPattern();

        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::SHORT,
            config('app.timezone'),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $datetime = $this->toDateTime();
        return $datetime ? ucfirst($formatter->format($datetime)) : null;
    }

    protected function toDateTime(): ?DateTime
    {
        $date = $this->date ?: now()->format('d-m-Y');
        $time = $this->showTime ? ($this->time ?: now()->format('H:i')) : '00:00';

        $datetime = DateTime::createFromFormat('d-m-Y H:i', "$date $time")
                  ?: DateTime::createFromFormat('Y-m-d H:i', "$date $time");

        return $datetime ?: null;
    }

    protected function getPattern(): string
    {
        $pattern = '';

        if ($this->showDate) {
            $pattern .= 'EEEE d MMMM';
            if ($this->showYear) {
                $pattern .= ' y';
            }
        }

        if ($this->showTime) {
            if ($pattern !== '') {
                $pattern .= ' \'a las\' ';
            }
            $pattern .= $this->is24h ? 'HH:mm' : 'h:mm a';
        }

        return $pattern;
    }

    public function render(): View|Closure|string
    {
        return view('components.date-time-display');
    }
}
