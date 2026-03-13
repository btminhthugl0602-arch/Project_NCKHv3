<?php

declare(strict_types=1);

function sp_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function sp_semantic_build_conjunctions(array $node): array
{
    $type = strtoupper(trim((string) ($node['type'] ?? '')));

    if ($type === 'RULE') {
        return [[[
            'attr' => (string) ($node['attr'] ?? ''),
            'op' => (string) ($node['op'] ?? ''),
            'val' => (string) ($node['val'] ?? ''),
        ]]];
    }

    if ($type !== 'GROUP') {
        return [];
    }

    $children = $node['children'] ?? [];
    if (!is_array($children) || count($children) !== 2) {
        return [];
    }

    $left = sp_semantic_build_conjunctions($children[0]);
    $right = sp_semantic_build_conjunctions($children[1]);
    $operator = strtoupper(trim((string) ($node['operator'] ?? 'AND')));

    if ($operator === 'OR') {
        return array_merge($left, $right);
    }

    $merged = [];
    foreach ($left as $l) {
        foreach ($right as $r) {
            $merged[] = array_merge($l, $r);
        }
    }
    return $merged;
}

function sp_semantic_find_conflicts(array $ast): array
{
    $conjunctions = sp_semantic_build_conjunctions($ast);
    $errors = [];

    foreach ($conjunctions as $branchIndex => $constraints) {
        $byAttr = [];
        foreach ($constraints as $constraint) {
            $attr = trim((string) ($constraint['attr'] ?? ''));
            if ($attr === '') {
                continue;
            }
            if (!isset($byAttr[$attr])) {
                $byAttr[$attr] = [];
            }
            $byAttr[$attr][] = $constraint;
        }

        foreach ($byAttr as $attr => $items) {
            $eqValues = [];
            foreach ($items as $item) {
                $op = trim((string) ($item['op'] ?? ''));
                $val = trim((string) ($item['val'] ?? ''));
                if ($op === '=') {
                    $eqValues[$val] = true;
                }
            }
            if (count($eqValues) > 1) {
                $errors[] = 'Branch #' . ($branchIndex + 1) . ' attr ' . $attr . ' has conflicting equals';
            }
        }
    }

    return $errors;
}

$validAst = [
    'type' => 'GROUP',
    'operator' => 'AND',
    'children' => [
        ['type' => 'RULE', 'attr' => 'GPA', 'op' => '>=', 'val' => '2.5'],
        ['type' => 'RULE', 'attr' => 'DRL', 'op' => '>=', 'val' => '70'],
    ],
];

$invalidAst = [
    'type' => 'GROUP',
    'operator' => 'AND',
    'children' => [
        ['type' => 'RULE', 'attr' => 'GPA', 'op' => '=', 'val' => '3.0'],
        ['type' => 'RULE', 'attr' => 'GPA', 'op' => '=', 'val' => '2.0'],
    ],
];

sp_assert_true(empty(sp_semantic_find_conflicts($validAst)), 'Valid AST must not produce semantic conflicts');
sp_assert_true(count(sp_semantic_find_conflicts($invalidAst)) > 0, 'Invalid AST must produce semantic conflicts');
