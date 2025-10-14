<!DOCTYPE html>
<html>
<head>
    <title>Debug - Available Brand IDs</title>
    <script src="assets/libs/jquery/jquery.min.js"></script>
</head>
<body>
    <h1>Available Brand IDs in Database</h1>
    <div id="brands"></div>

    <script>
        $(document).ready(function() {
            $.post('ajax/php/item-master.php', { action: 'debug_brands' }, function(response) {
                if (response.status === 'success') {
                    let html = '<table border="1"><tr><th>ID</th><th>Name</th></tr>';
                    response.brands.forEach(function(brand) {
                        html += '<tr><td>' + brand.id + '</td><td>' + brand.name + '</td></tr>';
                    });
                    html += '</table>';
                    $('#brands').html(html);
                } else {
                    $('#brands').html('<p>Error: ' + response.message + '</p>');
                }
            });
        });
    </script>
</body>
</html>
