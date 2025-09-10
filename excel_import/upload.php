<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>File Upload Example</title>
	<!-- Include Tailwind CSS -->
	<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
	<div class="max-w-md mx-auto p-4 mt-8 bg-white rounded-lg shadow-md">
		<h2 class="text-2xl font-semibold mb-4">File Upload</h2>
		<form id="uploadForm" enctype="multipart/form-data">
			<div class="mb-4">
				<label class="block text-gray-600">Select File</label>
				<input type="file" name="file" class="border rounded-lg p-2 w-full">
			</div>
			<div class="mb-4">
				<label class="block text-gray-600">Check Headers</label>
				<input type="checkbox" name="hasHeaders" class="mr-2">
				<span class="text-gray-600">Check if the uploaded file has headers</span>
			</div>
			<button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">Upload</button>
		</form>
		<div id="progress" class="mt-4"></div>
		<div id="response" class="mt-4"></div>
	</div>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#uploadForm').submit(function(e) {
				e.preventDefault();

				var formData = new FormData(this);

				$.ajax({
					url: 'import_office.php',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					xhr: function() {
						var xhr = new window.XMLHttpRequest();
						xhr.upload.addEventListener('progress', function(evt) {
							if (evt.lengthComputable) {
								var percentComplete = (evt.loaded / evt.total) * 100;
								$('#progress').text('Uploading: ' + percentComplete.toFixed(2) + '%');
							}
						}, false);
						return xhr;
					},
					success: function(response) {
						$('#progress').text('Upload complete!');
						$('#response').text(response);
					},
					error: function() {
						$('#progress').text('Error uploading file.');
					}
				});
			});
		});
	</script>
</body>

</html>