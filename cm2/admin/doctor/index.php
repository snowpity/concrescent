<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>CONcrescent Doctor</title>
<style>
	th { width: 100px; }
	.ok { background: green; color: white; }
	.wn { background: yellow; color: black; }
	.ng { background: red; color: white; }
</style>
<script>
'use strict';
document.addEventListener('DOMContentLoaded', function()
{
	const TEST_STATUS = Object.freeze({
		GOOD: Object.freeze({class: 'ok', name: 'PASSED'}),
		WARN: Object.freeze({class: 'wn', name: 'NOTICE'}),
		FAIL: Object.freeze({class: 'ng', name: 'FAILED'}),
	});

	function set_row(row, status, message)
	{
		row.classList.add(status.class);
		row.querySelector('th').textContent = status.name;
		row.querySelector('td').textContent = message;
	}

	function run_test(row)
	{
		/*return*/ fetch(row.id + '.php', {
			method: 'GET',
			cache: 'no-store'
		})
		.then(response => {
			if(!response.ok)
			{
				throw 'Test "' + row.id + '" failed to run. HTTP status code: ' + response.status;
			}
			return response.text();
		})
		.then(data => {
			// NOTE: This will blow up if the response isn't at least 3 characters.
			const statusCode = data.substring(0, 3);
			const message = data.substring(3);

			switch(statusCode)
			{
				case 'OK ':
					set_row(row, TEST_STATUS.GOOD, message);
					break;
				case 'WN ':
					set_row(row, TEST_STATUS.WARN, message);
					break;
				case 'NG ':
					set_row(row, TEST_STATUS.FAIL, message);
					break;
				default:
					set_row(row, TEST_STATUS.FAIL, 'Test "' + row.id + '" failed to run. Response: ' + data);
					break;
			}
		})
		.catch(error => {
			if(typeof error === 'string')
			{
				set_row(row, TEST_STATUS.FAIL, error);
			}
			else
			{
				console.error(error);
				set_row(row, TEST_STATUS.FAIL, 'Test "' + row.id + '" failed to run. Check the console for exception details.');
			}
		});
	}

	// This runs the tests in parallel, as fast as possible.
	// To run the tests sequentially, uncomment the `return` in` `run_test`.
	const rows = document.getElementsByTagName('tr');
	Array.prototype.reduce.call(rows, (promise, row) => {
		return promise.then(() => run_test(row));
	}, Promise.resolve());
});
</script>
</head>
<body>
<table border="1" cellspacing="0" cellpadding="4">
	<tr id="https">
		<th>CHECKING</th>
		<td>Checking HTTPS...</td>
	</tr>
	<tr id="phpversion">
		<th>CHECKING</th>
		<td>Checking PHP version...</td>
	</tr>
	<tr id="config1">
		<th>CHECKING</th>
		<td>Checking configuration file can be loaded...</td>
	</tr>
	<tr id="config2">
		<th>CHECKING</th>
		<td>Checking all configuration sections are present...</td>
	</tr>
	<tr id="config3">
		<th>CHECKING</th>
		<td>Checking database configuration...</td>
	</tr>
	<tr id="config4">
		<th>CHECKING</th>
		<td>Checking PayPal configuration...</td>
	</tr>
	<tr id="config5">
		<th>CHECKING</th>
		<td>Checking default administrator user...</td>
	</tr>
	<tr id="database1">
		<th>CHECKING</th>
		<td>Checking database connection...</td>
	</tr>
	<tr id="database2">
		<th>CHECKING</th>
		<td>Checking database connection through CONcrescent...</td>
	</tr>
	<tr id="database3">
		<th>CHECKING</th>
		<td>Checking database date and time...</td>
	</tr>
	<tr id="database4">
		<th>CHECKING</th>
		<td>Checking database character set...</td>
	</tr>
	<tr id="database5">
		<th>CHECKING</th>
		<td>Checking user accounts...</td>
	</tr>
	<tr id="curl">
		<th>CHECKING</th>
		<td>Checking cURL extension...</td>
	</tr>
	<tr id="paypal">
		<th>CHECKING</th>
		<td>Checking PayPal connection...</td>
	</tr>
	<tr id="mail">
		<th>CHECKING</th>
		<td>Checking email sending capability...</td>
	</tr>
	<tr id="gd">
		<th>CHECKING</th>
		<td>Checking GD library...</td>
	</tr>
	<tr id="theme">
		<th>CHECKING</th>
		<td>Checking theme stylesheet...</td>
	</tr>
</table>
</body>
</html>