<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$weatherApiKey = $_ENV['WEATHER_API_KEY'];
$mapApiKey = $_ENV['MAP_API_KEY'];

function getWeatherData(float $lat, float $lon) {
    global $weatherApiKey;
    $weatherApiUrl = "https://api.openweathermap.org/data/2.5/weather?";
    $params = [
        'lat' => $lat,
        'lon' => $lon,
        'units' => 'metric',
        'appid' => $weatherApiKey
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $weatherApiUrl . http_build_query($params));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    curl_close($ch);
    $data = json_decode($response);
    return $data;
}

function getLocationData(float $lat, float $lon) {
    global $mapApiKey;

    $mapApiUrl = "http://dev.virtualearth.net/REST/v1/Locations/" . $lat . ",%20" . $lon . "?";

    $params = [
        'o' => 'json',
        'c' => 'ES',
        'key' => $mapApiKey
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $mapApiUrl . http_build_query($params));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    curl_close($ch);
    $data = json_decode($response);
    return $data;
}

if (!empty($_POST)) {
    $lat = filter_input(INPUT_POST, "lat", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $lon = filter_input(INPUT_POST, "lon", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if (isset($_POST['boton_tiempo'])) {
        $data = getWeatherData($lat, $lon);
        $tiempo = $data->weather[0]->main;
        $temperatura = $data->main->temp;
        $humedad = $data->main->humidity;
    } else if (isset($_POST['boton_direccion'])) {
        $data = getLocationData($lat, $lon);
        if (empty($data->resourceSets[0]->resources)) {
            $localidad = "No ubicable";
            $ciudad = "No ubicable";
            $pais = "No ubicable";
        } else {
            $direccion = $data->resourceSets[0]->resources[0]->address;
            $localidad = $direccion->adminDistrict ?? '';
            $ciudad = $direccion->locality ?? $direccion->adminDistrict2 ?? '';
            $pais = $direccion->countryRegion ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width = device-width, initial-scale = 1.0">
        <!-- Bootstrap CDN -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <!--Bootstrap Font Icon CSS-->
        <link rel = "stylesheet" href = "https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
        <title>App Híbrida</title>
    </head>
    <body style = "background:#00bfa5;">
        <div class = "container mt-3">
            <form method = "POST" action = '{{ $_SERVER['PHP_SELF'] }}'>
                <div class = "d-flex justify-content-center h-100">
                    <div class = "card" style = 'width:28rem;'>
                        <div class = "card-header">
                            <h3><i class = "bi bi-gear-fill mr-1"></i>Api REST</h3>
                        </div>
                        <div class = "card-body">
                            <div class = "input-group my-2">
                                <span class = "input-group-text"><i class = "bi bi-geo-alt-fill"></i></span>
                                <input name = "lat" type = "number" class = "form-control" placeholder = "Latitud" id = 'lat' step = '0.000001' 
                                       value = "<?= $lat ?? '' ?>" required>
                            </div>
                            <div class="input-group my-2">                               
                                <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>      
                                <input name="lon" type="number" class="form-control" placeholder="longitud" id='lon' step="0.000001" 
                                    value = "<?= $lon ?? '' ?>" required>
                            </div>
                            <div class="form-group my-2">
                                <input type="submit" name="boton_direccion" class="btn btn-info mr-2" id="vDireccion" value="Ver Direccion" />
                                <input type="submit" name="boton_tiempo" class="btn btn-success" id="vTiempo" value="Ver Tiempo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center h-100 mt-2" id="datos">
                    <div class="card" style='width:28rem;'>
                        <div class="card-body">
                            <div class="input-group my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-pin-fill"></i></span>
                                <input type="text" name="localidad" class="form-control" placeholder="Localidad" id='dir' readonly value="<?= $localidad ?? ''?>">
                            </div>
                            <div class="input-group my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-building"></i></span>
                                <input type="text" name="ciudad" class="form-control" placeholder="Ciudad" id="ciu" readonly value="<?= $ciudad ?? ''?>">
                            </div>
                            <div class="input-group my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-globe"></i></span>
                                <input type="text" name="pais" class="form-control" placeholder="País" id="pai" readonly value="<?= $pais ?? ''?>">
                            </div>
                            <div class="input-group my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-umbrella-fill"></i></span>
                                <input type="text" name="tiempo" class="form-control" placeholder="Tiempo" id="tie" readonly  value="<?= $tiempo ?? ''?>">
                            </div>
                            <div class="input-group my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-thermometer-half"></i></span>
                                <input type="text" name="temperatura" class="form-control" placeholder="Temperatura (ºC)" id="tem" readonly value="<?= $temperatura ?? ''?>">
                            </div>
                            <div class="input-group form my-2">
                                <span class="input-group-text" style="width:2.5rem;"><i class="bi bi-droplet-fill"></i></span>
                                <input type="text" name="humedad" class="form-control" placeholder="Humedad (%)" id="hum" readonly value="<?= $humedad ?? ''?>">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    </body>
</html>