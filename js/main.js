
function exportTo(type,filess) {

	$('.table').tableExport({
		filename: filess,
		format: type,
		cols: '2,3,4'
	});

}

function exportAll(type,filess) {

	$('.table').tableExport({
		filename: filess,
		format: type
	});

}