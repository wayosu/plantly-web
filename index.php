<?php
// connection to firebase - realtime database
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/secret/myrtdb.json')
    ->withDatabaseUri('https://app-single-php-file-default-rtdb.asia-southeast1.firebasedatabase.app/');

$realtimeDatabase = $factory->createDatabase();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_button'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = $_POST['image'] ?? '';

    if (empty($name)) {
        $error_name_msg = 'Please fill the name field.';
    } else {
        $name = validateInput($name);
    }

    if (empty($description)) {
        $error_description_msg = 'Please fill the description field.';
    } else {
        $description = validateInput($description);
    }

    if (empty($image)) {
        $error_image_msg = 'Please fill the image field.';
    } else {
        $image = validateInput($image);
    }

    // insert data
    if (empty($error_name_msg) && empty($error_description_msg) && empty($error_image_msg)) {
        try {
            $plantCode = 'PNC#' . random_char(8);

            $realtimeDatabase->getReference('plants')->push([
                'plant_code' => $plantCode,
                'name' => $name,
                'description' => $description,
                'image' => $image,
            ]);

            echo "
                <script>
                    alert('Data added successfully!');
                    window.location.href = '/plants';
                </script>
            ";

            exit();
        } catch (\Kreait\Firebase\Exception\DatabaseException $e) {
            echo "
                <script>
                    alert('Error: ' " . $e->getMessage() . ");
                </script>
            ";

            exit();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo "
                <script>
                    alert('Error: ' " . $e->getMessage() . ");
                </script>
            ";

            exit();
        }
    }
}

function validateInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk menghasilkan string acak dengan panjang tertentu, termasuk angka
function random_char($length)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomString = '';
    $timestamp = microtime(true); // Ambil timestamp saat ini
    $timestamp = (int)($timestamp * 1000); // Ubah timestamp menjadi integer

    // Menggunakan timestamp untuk menghasilkan string acak
    for ($i = 0; $i < $length; $i++) {
        $index = $timestamp % strlen($characters); // Tentukan indeks karakter
        $randomString .= $characters[$index];
        $timestamp = (int)($timestamp / strlen($characters)); // Perbarui timestamp
    }

    return $randomString;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_button'])) {
    $plantCode = $_POST['plant_code'] ?? '';

    if (!empty($plantCode)) {
        try {
            // Ambil referensi ke 'plants' dan cari data berdasarkan 'plant_code'
            $reference = $realtimeDatabase->getReference('plants');
            $query = $reference->orderByChild('plant_code')->equalTo($plantCode);

            // Ambil snapshot data
            $snapshot = $query->getSnapshot();

            if ($snapshot->exists()) {
                // Jika data ditemukan, hapus data tersebut
                foreach ($snapshot->getValue() as $key => $value) {
                    // Hapus data dengan key yang ditemukan
                    $realtimeDatabase->getReference('plants/' . $key)->remove();
                }

                // Output JavaScript untuk alert dan redirect
                echo "
                    <script>
                        alert('Data deleted successfully!');
                        window.location.href = '/plants';
                    </script>
                ";

                exit();
            } else {
                // Jika data tidak ditemukan
                echo "
                    <script>
                        alert('Data not found!');
                        window.location.href = '/plants';
                    </script>
                ";

                exit();
            }
            exit(); // Pastikan eksekusi PHP berhenti setelah output
        } catch (\Kreait\Firebase\Exception\DatabaseException $e) {
            echo "
                <script>
                    alert('Error: ' " . $e->getMessage() . ");
                </script>
            ";

            exit();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo "
                <script>
                    alert('Error: ' " . $e->getMessage() . ");
                </script>
            ";

            exit();
        }
    } else {
        echo "
            <script>
                alert('No plant code provided!');
                window.location.href = '/plants';
            </script>
        ";
        exit(); // Pastikan eksekusi PHP berhenti setelah output
    }
}

// Cek apakah form sedang dalam mode edit
if ($_SERVER['REQUEST_METHOD'] == 'GET' && strpos($_SERVER['REQUEST_URI'], '/plants/edit-data') !== false) {
    // Ambil plant_code dari query string
    $url = parse_url($_SERVER['REQUEST_URI']);
    parse_str($url['query'] ?? '', $query);

    // Ambil plant_code dari query string jika ada
    $plantCode = base64_decode($query['plant_code'] ?? '');

    if (!empty($plantCode)) {
        try {
            $reference = $realtimeDatabase->getReference('plants');
            $query = $reference->orderByChild('plant_code')->equalTo($plantCode);
            $snapshot = $query->getSnapshot();

            if ($snapshot->exists()) {
                $data = $snapshot->getValue();
                $item = array_shift($data); // Ambil item pertama dari hasil query

                // Isi variabel dengan data yang ada
                $currentPlantCode = $item['plant_code'] ?? '';
                $currentName = $item['name'] ?? '';
                $currentDescription = $item['description'] ?? '';
                $currentImage = $item['image'] ?? '';
            } else {
                echo "
                    <script>
                        alert('Data not found!');
                        window.location.href = '/plants';
                    </script>
                ";
                exit();
            }
        } catch (\Kreait\Firebase\Exception\DatabaseException $e) {
            echo "
                <script>
                    alert('Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "');
                    window.location.href = '/plants';
                </script>
            ";
            exit();
        }
    } else {
        // Jika plant_code tidak ada
        echo "
            <script>
                alert('No plant code provided!');
                window.location.href = '/plants';
            </script>
        ";
        exit();
    }
}

// Pastikan ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_button'])) {
    // Ambil data dari form
    $plantCode = $_POST['plant_code'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = $_POST['image'] ?? '';

    if (empty($name)) {
        $error_name_msg = 'Please fill the name field.';
    } else {
        $name = validateInput($name);
    }

    if (empty($description)) {
        $error_description_msg = 'Please fill
        the description field.';
    } else {
        $description = validateInput($description);
    }

    if (empty($image)) {
        $error_image_msg = 'Please fill the image field.';
    } else {
        $image = validateInput($image);
    }

    // Update data jika tidak ada error
    if (empty($error_name_msg) && empty($error_description_msg) && empty($error_image_msg)) {
        try {
            $reference = $realtimeDatabase->getReference('plants');
            $query = $reference->orderByChild('plant_code')->equalTo($plantCode);
            $snapshot = $query->getSnapshot();

            if ($snapshot->exists()) {
                $data = $snapshot->getValue();
                $key = array_key_first($data);

                // Update data
                $updates = [
                    'name' => $name,
                    'description' => $description,
                    'image' => $image
                ];

                $reference->getChild($key)->update($updates);

                echo "
                    <script>
                        alert('Data updated successfully!');
                        window.location.href = '/plants';
                    </script>
                ";
                exit();
            } else {
                echo "
                    <script>
                        alert('Plant not found!');
                        window.location.href = '/plants';
                    </script>
                ";
                exit();
            }
        } catch (\Kreait\Firebase\Exception\DatabaseException $e) {
            echo "
                <script>
                    alert('Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "');
                    window.location.href = '/plants';
                </script>
            ";
            exit();
        }
    }
}

$url = parse_url($_SERVER['REQUEST_URI']);

$plants = $realtimeDatabase->getReference('plants')->getValue();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hello Bulma!</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css">
    <style>
        .my-custom-navbar-title {
            font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
            letter-spacing: .1em;
        }

        .my-custom-card-image img {
            width: 100%;
            height: auto;
            aspect-ratio: 1/1;
            object-fit: cover;
        }

        .my-custom-table-image img {
            width: 40px;
            height: auto;
            aspect-ratio: 1/1;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <nav class="navbar py-4" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="/">
                    <img src="https://cdn-icons-png.freepik.com/256/9039/9039858.png" alt="icon">
                    <span class="my-custom-navbar-title has-text-weight-bold is-size-5">Plantly</span>
                </a>

                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarBasicExample">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>

            <div id="navbarBasicExample" class="navbar-menu">
                <div class="navbar-end">
                    <div class="navbar-item">
                        <div class="buttons">
                            <?php if ($_SERVER['REQUEST_URI'] == '/plants' || $_SERVER['REQUEST_URI'] == '/plants/add-data' || isset($url['path']) && $url['path'] == '/plants/edit-data'): ?>
                                <a href="/" class="button is-primary">
                                    Home
                                </a>
                            <?php else: ?>
                                <a href="/plants" class="button is-primary">
                                    <strong>Plant Data List</strong>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <?php if ($_SERVER['REQUEST_URI'] == '/plants'): ?>
                <div class="is-flex is-align-items-center is-justify-content-space-between mb-4">
                    <h1 class="title is-4 mb-0">Plant Data List</h1>
                    <a href="/plants/add-data" class="button is-info is-dark">Add Data</a>
                </div>
                <div class="card">
                    <div class="card-content">
                        <div class="content">
                            <?php if ($plants): ?>
                                <table class="table is-fullwidth is-striped is-hoverable">
                                    <thead>
                                        <tr>
                                            <th>Plant Code</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plants as $key => $plant): ?>
                                            <tr>
                                                <td><span class="tag is-normal"><?= $plant['plant_code'] ?></span></td>
                                                <td><?= $plant['name'] ?></td>
                                                <td><?= $plant['description'] ?></td>
                                                <td>
                                                    <figure class="my-custom-table-image">
                                                        <img src="<?= $plant['image'] ?>" alt="Placeholder image" />
                                                    </figure>
                                                </td>
                                                <td>
                                                    <div class="buttons">
                                                        <a href="/plants/edit-data?plant_code=<?= base64_encode($plant['plant_code']); ?>" class="button is-warning is-dark">Edit</a>
                                                        <form action="<?= htmlspecialchars('/plants'); ?>" method="POST">
                                                            <input type="hidden" name="plant_code" value="<?= $plant['plant_code'] ?>">
                                                            <button type="submit" name="delete_button" class="button is-danger is-dark" onclick="return confirm('Are you sure you want to delete this data?');">Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="has-text-centered">No data found.</p>
                            <?php endif;  ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($_SERVER['REQUEST_URI'] == '/plants/add-data' || (isset($url['path']) && $url['path'] == '/plants/edit-data')): ?>
                <div class="is-flex is-align-items-center is-justify-content-space-between mb-4">
                    <?php if (isset($url['path']) && $url['path'] == '/plants/edit-data'): ?>
                        <h1 class="title is-4 mb-0">Edit Plant Data</h1>
                    <?php else: ?>
                        <h1 class="title is-4 mb-0">Add Plant Data</h1>
                    <?php endif; ?>
                    <a href="/plants" class="button is-dark">Back</a>
                </div>
                <div class="card">
                    <div class="card-content">
                        <div class="content">
                            <form action="<?= htmlspecialchars((isset($url['path']) && $url['path'] == '/plants/edit-data') ? '/plants/edit-data' : '/plants/add-data'); ?>" method="POST">
                                <?php if (isset($url['path']) && $url['path'] == '/plants/edit-data'): ?>
                                    <input type="hidden" name="plant_code" value="<?= htmlspecialchars($currentPlantCode ?? ''); ?>">
                                <?php endif; ?>
                                <div class="field">
                                    <label class="label">Name</label>
                                    <div class="control">
                                        <input class="input" name="name" type="text" placeholder="Plant name" value="<?= htmlspecialchars($currentName ?? ($name ?? '')); ?>">
                                    </div>
                                    <?php if (isset($error_name_msg)): ?>
                                        <p class="help is-danger"><?= $error_name_msg ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="field">
                                    <label class="label">Description</label>
                                    <div class="control">
                                        <textarea class="textarea" name="description" placeholder="Plant description"><?= htmlspecialchars($currentDescription ?? ($description ?? '')); ?></textarea>
                                    </div>
                                    <?php if (isset($error_description_msg)): ?>
                                        <p class="help is-danger"><?= $error_description_msg ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="field">
                                    <label class="label">Image</label>
                                    <div class="control">
                                        <input class="input" type="text" name="image" placeholder="Link to image png/jpg/jpeg" value="<?= htmlspecialchars($currentImage ?? ($image ?? '')); ?>">
                                    </div>
                                    <?php if (isset($error_image_msg)): ?>
                                        <p class="help is-danger"><?= $error_image_msg ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="field is-grouped">
                                    <div class="control">
                                        <button type="submit" name="<?= (isset($url['path']) && $url['path'] == '/plants/edit-data') ? 'update_button' : 'add_button'; ?>" class="button is-link">
                                            <?= (isset($url['path']) && $url['path'] == '/plants/edit-data') ? 'Update' : 'Add'; ?>
                                        </button>
                                    </div>
                                    <div class="control">
                                        <button type="reset" class="button is-link is-light">Reset</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="columns is-multiline">
                    <?php foreach ($plants as $key => $plant): ?>
                        <div class="column is-3">
                            <div class="card">
                                <div class="card-image">
                                    <figure class="my-custom-card-image">
                                        <img
                                            src="<?= $plant['image'] ?>"
                                            alt="Placeholder image" />
                                    </figure>
                                </div>
                                <div class="card-content">
                                    <div class="content">
                                        <span class="tag is-normal"><?= $plant['plant_code'] ?></span>
                                        <h1 class="title is-4 my-4"><?= $plant['name'] ?></h1>
                                        <p class="mb-0">
                                            <?= strlen($plant['description']) > 100 ? substr($plant['description'], 0, 100) . '...' : $plant['description'] ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>

</html>