<?php

namespace Tests\Unit;

use App\Services\Workflow\WorkflowValidator;
use PHPUnit\Framework\TestCase;

class WorkflowValidatorTest extends TestCase
{
    private function kinds(string ...$kinds): array
    {
        return array_map(fn ($k) => ['kind' => $k], $kinds);
    }

    public function test_default_order_is_valid(): void
    {
        $this->assertSame([], WorkflowValidator::errors($this->kinds('draft', 'ready_for_tender', 'tender', 'ready_for_evaluation', 'evaluated', 'ordered', 'completed')));
    }

    public function test_first_must_be_draft_and_last_completed(): void
    {
        $errors = WorkflowValidator::errors($this->kinds('tender', 'draft'));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('erste Schritt', implode(' ', $errors));
        $this->assertStringContainsString('letzte Schritt', implode(' ', $errors));
    }

    public function test_steps_cannot_run_against_their_logical_rank(): void
    {
        $errors = WorkflowValidator::errors($this->kinds('draft', 'evaluated', 'tender', 'ordered', 'completed'));
        $this->assertNotEmpty($errors);
    }

    public function test_custom_steps_may_sit_anywhere_between(): void
    {
        $this->assertSame([], WorkflowValidator::errors($this->kinds('draft', 'custom', 'tender', 'custom', 'evaluated', 'ordered', 'completed')));
    }

    private function steps(array ...$steps): array
    {
        return array_map(fn ($s) => $s + ['label' => $s['kind']], $steps);
    }

    public function test_fixations_in_logical_order_are_valid(): void
    {
        $steps = $this->steps(
            ['kind' => 'draft', 'scopes' => []],
            ['kind' => 'tender', 'scopes' => ['products', 'tender']],
            ['kind' => 'evaluated', 'scopes' => ['offers']],
            ['kind' => 'ordered', 'scopes' => ['awarding']],
            ['kind' => 'completed', 'scopes' => []],
        );

        $this->assertSame([], WorkflowValidator::fixationErrors($steps, 'product'));
    }

    public function test_a_fixation_may_only_appear_once(): void
    {
        $steps = $this->steps(
            ['kind' => 'draft', 'scopes' => ['products']],
            ['kind' => 'tender', 'scopes' => ['products']],
        );

        $this->assertStringContainsString('nur einmal', implode(' ', WorkflowValidator::fixationErrors($steps, 'product')));
    }

    public function test_offers_cannot_be_fixed_before_offers_exist(): void
    {
        $steps = $this->steps(
            ['kind' => 'draft', 'scopes' => ['offers']],
            ['kind' => 'tender', 'scopes' => []],
        );

        $this->assertNotEmpty(WorkflowValidator::fixationErrors($steps, 'product'));
    }

    public function test_awarding_cannot_be_fixed_before_offers_fixation(): void
    {
        $steps = $this->steps(
            ['kind' => 'draft', 'scopes' => []],
            ['kind' => 'tender', 'scopes' => []],
            ['kind' => 'evaluated', 'scopes' => ['awarding']],
            ['kind' => 'ordered', 'scopes' => ['offers']],
        );

        $this->assertStringContainsString('darf nicht vor', implode(' ', WorkflowValidator::fixationErrors($steps, 'product')));
    }

    public function test_tender_text_cannot_be_fixed_in_vehicle_workflows(): void
    {
        $steps = $this->steps(['kind' => 'draft', 'scopes' => []], ['kind' => 'tender', 'scopes' => ['tender']]);

        $this->assertNotEmpty(WorkflowValidator::fixationErrors($steps, 'vehicle'));
        $this->assertSame([], WorkflowValidator::fixationErrors($steps, 'product'));
    }
}
