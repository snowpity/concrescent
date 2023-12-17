<?php

function cm_form_questions_to_csv_columns($questions) {
	$columns = array();
	$ignored_question_types = array('h1', 'h2', 'h3', 'p', 'q', 'hr');
	foreach ($questions as $question) {
		if ($question['active'] && !in_array($question['type'], $ignored_question_types)) {
			$columns[] = array(
				'key' => 'form-answer-array-' . $question['question-id'],
				'name' => $question['title'],
				'type' => 'array'
			);
		}
	}
	return $columns;
}

function cm_output_csv(&$columns, &$entities, $filename) {
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename=' . $filename);
	header('Pragma: no-cache');
	header('Expires: 0');
	$out = fopen('php://output', 'w');

	$row = array();
	foreach ($columns as $column) {
		$row[] = $column['name'];
	}
	fputcsv($out, $row);

	foreach ($entities as $entity) {
		$row = array();
		foreach ($columns as $column) {
			$key = $column['key'];
			$value = $entity[$key] ?? null;
			if (is_null($value)) {
				$row[] = '';
			} else {
				switch ($column['type']) {
					case 'bool':
						$row[] = $value ? 'Yes' : 'No';
						break;
					case 'int':
						$row[] = (int)$value;
						break;
					case 'float':
						$row[] = (float)$value;
						break;
					case 'price':
						$row[] = number_format($value, 2, '.', '');
						break;
					case 'array':
						$row[] = implode("\n", $value);
						break;
					default:
						$row[] = $value;
						break;
				}
			}
		}
		fputcsv($out, $row);
	}

	fclose($out);
	exit(0);
}
