<?php
/**
 * This source file is proprietary and part of Rebilly.
 *
 * (c) Rebilly SRL
 *     Rebilly Ltd.
 *     Rebilly Inc.
 *
 * @see https://www.rebilly.com
 */

$header = <<<'EOF'
This source file is proprietary and part of Rebilly.

(c) Rebilly SRL
    Rebilly Ltd.
    Rebilly Inc.

@see https://www.rebilly.com
EOF;

$rules = [
    '@PSR2' => true,
    'align_multiline_comment' => true,
    'array_syntax' => ['syntax' => 'short'],
    'blank_line_before_statement' => true,
    'binary_operator_spaces' => true,
    'cast_spaces' => true,
    'class_attributes_separation' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'combine_nested_dirname' => true,
    'compact_nullable_typehint' => true,
    'concat_space' => ['spacing' => 'one'],
    'declare_equal_normalize' => true,
    // Useful but dangerous fixer. Requires a lot of changes and testing.
    // 'declare_strict_types' => true,
    'dir_constant' => true,
    'header_comment' => [
        'header' => $header,
        'commentType' => 'PHPDoc',
        'separate' => 'bottom',
        'location' => 'after_open',
    ],
    // Requires php 7.3+.
    // 'heredoc_indentation' => true,
    'heredoc_to_nowdoc' => true,
    'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'version']],
    'is_null' => true,
    'list_syntax' => ['syntax' => 'short'],
    'lowercase_cast' => true,
    'lowercase_static_reference' => true,
    'magic_constant_casing' => true,
    'magic_method_casing' => true,
    'mb_str_functions' => true,
    'method_argument_space' => ['ensure_fully_multiline' => true],
    'method_chaining_indentation' => true,
    'modernize_types_casting' => true,
    'native_function_casing' => true,
    // We might want this in future.
    // 'native_function_invocation' => true,
    'new_with_braces' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_comment' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => ['tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']],
    'no_homoglyph_names' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => true,
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_null_property_initialization' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_spaces_around_offset' => true,
    'no_superfluous_elseif' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    'no_trailing_comma_in_singleline_array' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unneeded_curly_braces' => true,
    'no_unneeded_final_method' => true,
    'no_unreachable_default_argument_value' => true,
    'no_unset_on_property' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'normalize_index_brace' => true,
    'ordered_class_elements' => true,
    'ordered_imports' => true,
    'php_unit_construct' => true,
    'php_unit_dedicate_assert' => ['target' => 'newest'],
    'php_unit_expectation' => true,
    'php_unit_method_casing' => true,
    'php_unit_mock' => true,
    'php_unit_namespaced' => true,
    'php_unit_no_expectation_annotation' => true,
    'php_unit_ordered_covers' => true,
    'php_unit_set_up_tear_down_visibility' => true,
    'php_unit_strict' => true,
    'php_unit_test_annotation' => ['style' => 'annotation'],
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
    'phpdoc_annotation_without_dot' => true,
    'phpdoc_indent' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_order' => true,
    'phpdoc_scalar' => true,
    'phpdoc_separation' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_trim' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_types' => true,
    'phpdoc_types_order' => true,
    'pow_to_exponentiation' => true,
    'protected_to_private' => true,
    // This does not work well with test cases ATM.
    // 'psr4' => true,
    'random_api_migration' => true,
    'return_type_declaration' => true,
    'self_accessor' => true,
    'semicolon_after_instruction' => true,
    'short_scalar_cast' => true,
    'single_line_comment_style' => true,
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'strict_comparison' => true,
    'strict_param' => true,
    'ternary_operator_spaces' => true,
    'ternary_to_null_coalescing' => true,
    'trailing_comma_in_multiline_array' => true,
    'trim_array_spaces' => true,
    'unary_operator_spaces' => true,
    'visibility_required' => ['property', 'method', 'const'],
    'void_return' => true,
    'whitespace_after_comma_in_array' => true,
    'yoda_style' => ['equal' => false, 'identical' => false],
    'object_operator_without_whitespace' => true,
];

$includeDirs = [
    __DIR__ . '/src',
    __DIR__ . '/tests',
];

$excludeDirs = [];

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(true)
    ->exclude($excludeDirs)
    ->in($includeDirs);

$config = PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);

return $config;
