<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//IT"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Usetiful</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <script type="text/javascript" src="scripts/jquery-1.8.3.js"></script>
        <script type="text/javascript" src="scripts/curvycorners.js"></script>
        <link rel="stylesheet" type="text/css" href="css/style.css"/>
        <meta name="author" content="Belotti Alberto"/>
        <meta http-equiv="reply-to" content="albertopuntobelotti@gmail.com"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="description" content='use beatiful'/>
        <meta name="keywords" content='Class, generator, orm, php'/>

        <script type="text/javascript">
            $(document).ready(function() {
                $('#result').hide();
                $('#table_name').change(function(){
                    $('#result').hide();
                });
                $('#button_send').click(function(){
                    if(($('#schema_name').val().length) == 0 || ($('#project_name').val().length) == 0){
                        $('#result').show();
                        $('#result').html('Attenzione!Schema name / Project name sono campi obbligatori!');
                        return false;
                    }
                    $('#result').show();
                    $.ajax({
                        url: 'class_generator_from_db.php',
                        data: { table_name: $('#table_name').val(), schema_name: $('#schema_name').val(),project_name: $('#project_name').val(),host: $('#host').val(),username: $('#username').val()   ,password: $('#password').val() 
					},
                        success: function(data) {
                            $('#result').html(data);
                        }
                    });
                });
            });
        </script>
    </head>
    <body>
        <div id="container">
            <div id="header"></div>
            <div id="main">
                <div id="main2">
                <? include 'include/menu.php' ?>
                <div id="mask">
                    <p class="caption">Class generator</p>
                    <p><span class="label">Host: </span><input type="text" id="host" class="textinput" value="127.0.0.1"/></p>
                    <p><span class="label">Username: </span><input type="text" id="username" class="textinput" value="root"/></p>
                    <p><span class="label">Password: </span><input type="text" id="password" class="textinput" value="alberto981"/></p>
                    <p><span class="label">Table Name: </span><input type="text" id="table_name" class="textinput" value=""/></p>
                    <p><span class="label">Schema Name: </span><input type="text" id="schema_name" class="textinput" value="cld"/></p>
                    <p><span class="label">Project Name (folder): </span><input type="text" id="project_name" class="textinput" value="cld2"/></p>
                    <div id="mask2"><a href="#" id="button_send"><img src="images/submit.png" border="0" alt="submit"/></a></div>
                    <div id="result"></div>
                </div>
                <div id="rightbar">Usetiful is... Use Beatiful!<br/><br/>Release 1.0</div>
                </div>
            </div>
        </div>
        <? include 'include/footer.php' ?>
    </body>
</html>
