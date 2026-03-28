<?php

declare(strict_types=1);

/**
 * Normalizes Pest-generated JUnit XML for Infection 0.32.x lookup behavior.
 *
 * Infection expects:
 * - `testsuite@name` and `testcase@class` to match one stable FQCN
 * - `testcase@file` to be a plain path (without "::test name" suffix)
 * - XPath queries to return at most one node for `//testcase[@class="..."][1]`
 *
 * Pest may emit multiple testcase entries with the same class across nested suites,
 * which makes Infection's strict XPath assertion fail. This script rewrites JUnit
 * metadata into a deterministic form that Infection can consume.
 */
if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/testing/normalize-pest-junit-for-infection.php <junit-xml-path>\n");
    exit(2);
}

$junitPath = $argv[1];

if (! is_file($junitPath) || ! is_readable($junitPath)) {
    fwrite(STDERR, "JUnit file not found or unreadable: {$junitPath}\n");
    exit(2);
}

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = true;
$dom->formatOutput = false;

if (! $dom->load($junitPath, LIBXML_PARSEHUGE)) {
    fwrite(STDERR, "Failed to parse JUnit XML: {$junitPath}\n");
    exit(1);
}

$xpath = new DOMXPath($dom);

function normalize_namespace_separators(string $class): string
{
    $trimmed = trim($class);

    if ($trimmed === '') {
        return $trimmed;
    }

    return str_replace('.', '\\', $trimmed);
}

function is_pest_style_class(string $class, ?string $filePath = null): bool
{
    $normalized = normalize_namespace_separators($class);

    if (str_ends_with($normalized, 'PestTest')) {
        return true;
    }

    if ($filePath === null || $filePath === '') {
        return false;
    }

    return str_ends_with($filePath, 'PestTest.php');
}

function normalize_to_infection_fqcn(string $class, ?string $filePath = null): string
{
    $normalized = normalize_namespace_separators($class);

    if ($normalized === '') {
        return $normalized;
    }

    if (is_pest_style_class($normalized, $filePath) && ! str_starts_with($normalized, 'P\\')) {
        $normalized = 'P\\'.$normalized;
    }

    return $normalized;
}

function derive_file_path_from_class(string $class): ?string
{
    $normalized = normalize_namespace_separators($class);

    if (str_starts_with($normalized, 'P\\')) {
        $normalized = substr($normalized, 2);
    }

    if (str_starts_with($normalized, 'Tests\\')) {
        return 'tests/'.str_replace('\\', '/', substr($normalized, strlen('Tests\\'))).'.php';
    }

    if (str_starts_with($normalized, 'Modules\\')) {
        return str_replace('\\', '/', $normalized).'.php';
    }

    return null;
}

/** @var array<string, int> $classSeen */
$classSeen = [];

/** @var array<string, bool> $aliasInserted */
$aliasInserted = [];

/** @var DOMElement $suite */
foreach ($xpath->query('//testsuite[@name]') as $suite) {
    $suiteName = $suite->getAttribute('name');
    $suiteFile = $suite->getAttribute('file');

    if ($suiteName !== '') {
        $normalizedSuiteName = normalize_to_infection_fqcn($suiteName, $suiteFile);
        $suite->setAttribute('name', $normalizedSuiteName);

        $derivedSuiteFile = derive_file_path_from_class($normalizedSuiteName);

        if ($derivedSuiteFile !== null && ($suiteFile === '' || ! str_ends_with($suiteFile, '.php'))) {
            $suite->setAttribute('file', $derivedSuiteFile);
        }
    }
}

/** @var DOMElement $testcase */
foreach ($xpath->query('//testcase') as $testcase) {
    $fileAttr = $testcase->getAttribute('file');

    if ($fileAttr !== '' && str_contains($fileAttr, '::')) {
        $parts = explode('::', $fileAttr, 2);
        $fileAttr = $parts[0];
        $testcase->setAttribute('file', $fileAttr);
    }

    $classAttr = $testcase->getAttribute('class');
    $classnameAttr = $testcase->getAttribute('classname');

    $baseClass = $classAttr !== ''
        ? normalize_to_infection_fqcn($classAttr, $fileAttr)
        : normalize_to_infection_fqcn($classnameAttr, $fileAttr);

    $derivedFile = $baseClass !== '' ? derive_file_path_from_class($baseClass) : null;

    if ($derivedFile !== null && ($fileAttr === '' || ! str_ends_with($fileAttr, '.php'))) {
        $fileAttr = $derivedFile;
        $testcase->setAttribute('file', $fileAttr);
    }

    if ($baseClass !== '') {
        $seen = $classSeen[$baseClass] ?? 0;
        $classSeen[$baseClass] = $seen + 1;

        if ($seen === 0) {
            $testcase->setAttribute('class', $baseClass);
            $testcase->setAttribute('classname', $baseClass);
        } else {
            // Keep classname informative, but make class unique so Infection's
            // query `//testcase[@class="..."][1]` resolves to exactly one node.
            $testcase->setAttribute('class', $baseClass.'#'.(string) ($seen + 1));
            $testcase->setAttribute('classname', $baseClass.'#'.(string) ($seen + 1));
        }

        $aliasClass = str_starts_with($baseClass, 'P\\') ? substr($baseClass, 2) : 'P\\'.$baseClass;

        if ($aliasClass !== '' && $aliasClass !== $baseClass && ! isset($aliasInserted[$aliasClass])) {
            $aliasNode = $testcase->cloneNode(true);

            if ($aliasNode instanceof DOMElement) {
                $aliasNode->setAttribute('class', $aliasClass);
                $aliasNode->setAttribute('classname', $aliasClass);

                $aliasFile = derive_file_path_from_class($aliasClass);

                if ($aliasFile !== null) {
                    $aliasNode->setAttribute('file', $aliasFile);
                }

                $parentNode = $testcase->parentNode;

                if ($parentNode !== null) {
                    $parentNode->appendChild($aliasNode);
                    $aliasInserted[$aliasClass] = true;
                }
            }
        }
    }
}

if ($dom->save($junitPath) === false) {
    fwrite(STDERR, "Failed to write normalized JUnit XML: {$junitPath}\n");
    exit(1);
}

fwrite(STDOUT, "Normalized Pest JUnit for Infection: {$junitPath}\n");
