<?php

use JetBrains\PhpStorm\NoReturn;

function cm_form_questions_to_csv_columns(array $questions): array
{
	$columns = array();
	$ignored_question_types = array('h1', 'h2', 'h3', 'p', 'q', 'hr');
	foreach ($questions as $question) {
		if ($question['active'] && !in_array($question['type'], $ignored_question_types)) {
			$columns[] = [
				'key' => 'form-answer-array-' . $question['question-id'],
				'name' => $question['title'],
				'type' => 'array'
			];
		}
	}
	return $columns;
}

#[NoReturn]
function cm_output_csv(array $columns, array $entities, string $filename): void
{
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename=' . $filename);
	header('Pragma: no-cache');
	header('Expires: 0');
	$out = fopen('php://output', 'wb');
	if ($out === false) {
		die('Can\'t open PHP output stream. Fatal error.');
	}

	$row = [];
	foreach ($columns as $column) {
		$row[] = $column['name'];
	}
	fputcsv($out, $row);

	foreach ($entities as $entity) {
		$row = [];
		foreach ($columns as $column) {
			$key = $column['key'];
			$value = $entity[$key] ?? null;
			if (is_null($value)) {
				$row[] = '';
			} else {
				$row[] = match ($column['type']) {
					'bool' => $value ? 'Yes' : 'No',
					'int' => (int)$value,
					'float' => (float)$value,
					'price' => number_format($value, 2, '.', ''),
					'array' => implode("\n", $value),
					default => $value,
				};
			}
		}
		fputcsv($out, $row);
	}

	fclose($out);
	exit(0);
}

#[NoReturn]
function cm_output_json(array $columns, array $entities, string $filename): void
{
	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename=' . $filename);
	header('Pragma: no-cache');
	header('Expires: 0');
	$out = fopen('php://output', 'wb');
	if ($out === false) {
		die('Can\'t open PHP output stream. Fatal error.');
	}

	fwrite($out, '[');

	$addAComma = false;
	foreach ($entities as $entity) {
		$row = [];
		foreach ($columns as $column) {
			$key = $column['key'];
			$value = $entity[$key] ?? null;
			if (is_null($value)) {
				$row[$key] = '';
			} else {
				$row[$key] = match ($column['type']) {
					'bool' => $value ? 'Yes' : 'No',
					'int' => (int)$value,
					'float' => (float)$value,
					'price' => number_format($value, 2, '.', ''),
					'array' => implode("\n", $value),
					default => $value,
				};
			}
		}

		if (!empty($row)) {
			fwrite($out,  ($addAComma ? ',' : '') . json_encode($row, JSON_THROW_ON_ERROR) . "\n");
			if (!$addAComma) {
				$addAComma = true;
			}
		}
	}

	fwrite($out, ']');

	fclose($out);
	exit(0);
}
