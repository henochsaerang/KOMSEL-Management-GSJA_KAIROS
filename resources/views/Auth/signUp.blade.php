<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sign Up</title>
</head>
<body>
    <div>
        <h1>Halaman Sign Up</h1>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            @method('POST')
            <div>
                <h4>
                    <label for="">
                        Nama:
                    </label><br>
                    <input type="text" name="nama">
                </h4>
            </div>
            <div>
                <h4>
                    <label for="">
                        Komsel:
                    </label><br>
                    <input type="text" name="komsel">
                </h4>
            </div>
            <div>
                <h4>
                <label for="">
                      Email:
                </label><br>
                </h4>
                <input type="email" name="email">
            </div>
            <div>
                <h4>
                    <label for="">
                        Password:
                    </label><br>
                    <input type="password" name="pass">
                </h4>
            </div>

            
            <br><br><div>
                <button type="submit">Sign-up</button>
            </div>
        </form>
    </div>
</body>
</html>