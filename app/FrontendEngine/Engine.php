<?php

namespace App\FrontendEngine;

/**
 * Result of validating a theme package (see EngineManager::validate()).
 * Immutable report: a list of checks, each pass/fail with a message.
 * A theme is only safe to activate when passed() is true.
 */
class Engine
{
    /** @param array<int, array{group: string, label: string, ok: bool, detail: ?string}> $checks */
    public function __construct(
        public readonly string $slug,
        public readonly array $checks,
    ) {
    }

    public function passed(): bool
    {
        foreach ($this->checks as $check) {
            if (! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, array{group: string, label: string, ok: bool, detail: ?string}> */
    public function failures(): array
    {
        return array_values(array_filter($this->checks, fn (array $check) => ! $check['ok']));
    }

    /** Checks grouped by their 'group' key, in encounter order — for rendering a report list. */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->checks as $check) {
            $groups[$check['group']][] = $check;
        }

        return $groups;
    }
}
