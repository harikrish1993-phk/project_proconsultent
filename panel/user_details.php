<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>User Details - <?php echo COMPANY_NAME; ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            line-height: 1.5em;
        }

        /* Container */
        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* Main Table */
        .main-table {
            width: 100%;
            max-width: 400px;            
            background-color: #ffffff;
            border: 1px solid #cccccc;
            border-spacing: 0;
            text-align: left;
        }

        /* Content */
        .content {
            padding: 0px 30px 42px 30px;
            color: #153643;
            font-size: 14px;
        }

        .img-class {
            display: block;
            margin: 0 auto;
            width: 65%;
        }
    </style>
</head>

<body>
    
    <div class="container">
            <table class="main-table" role="presentation">
                <tbody>
                    <!-- First Row: Image -->
                    <tr>
                        <td colspan="2" align="center">                            
                            <img class="img-class" src="{{user_header}}" style="width: 70%;padding: 3%;" />
                        </td>
                    </tr>
                    <!-- Second Row: Content -->
                    <tr>
                        <td colspan="2" class="content" style="padding-bottom:0px">
                            <p><strong>Dear {{user_name}},</strong></p>            
                            <p>Log in to the portal using your credentials:</p>
                            {{user_portal}}
                            <p><strong>User Id : {{user_id}}</strong></p>
                            <!-- <p>User Code : {{user_code}}</p> -->
                            <p><strong>Password : {{user_password}}</strong></p>
                            <br/><br/>
                            <p>Best Regards,</p>
                            <p><strong>Admin Department</strong><br/>                            
                            </p> 
                            <br/> <br/> 
                        </td>
                    </tr>
                    <!-- New Row: Additional Content -->
                    <tr>
                        <td colspan="2">
                            {{user_footer}}
                        </td>
                    </tr>
                    
                </tbody>
            </table>
    </div>
</body>

</html>
