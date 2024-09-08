<?php

/*if (isset($_POST['gz']) && !FM_READONLY) {
    try {
        $jsonName = 'data.json';
        $jsonFile = $root_url.'/'.FM_PATH.'/'.$jsonName;
        if (!file_exists($jsonFile)) {
            throw new Exception("Erro: O arquivo $jsonName não foi encontrado.");
        }
        $jsonData = file_get_contents($jsonFile);
        if ($jsonData === false) {
            throw new Exception("Erro ao ler o arquivo $jsonName.");
        }
        $jsonDecoded = json_decode($jsonData);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
        }
        $jsonDataSingleLine = json_encode($jsonDecoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonDataSingleLine === false) {
            throw new Exception("Erro ao codificar o JSON.");
        }
        $gzipFile = $jsonFile . '.gz';
        if (!$gz = gzopen($gzipFile, 'w9')) {
            throw new Exception("Erro ao criar o arquivo $gzipFile.");
        }
        gzwrite($gz, $jsonDataSingleLine);
        gzclose($gz);
        fm_set_msg("Arquivo <b>$jsonName</b> criado com sucesso!", 'ok');
    } catch (Exception $e) {
        fm_set_msg('<b>'.$e->getMessage().'</b>', 'error');
    }
}*/



$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$dir = $root_url.'/'.FM_PATH;

define('EM_URL', $protocol.$host);
define('EM_ARRAY_MULTIPLE_FOLDERS', ['atas', 'portarias', 'resolucoes']);
define('EM_ARRAY_SINGLE_FOLDERS', ['relatorios', 'secoes-administrativas']);
define('FOLDER_NAMES',  [
    'resolucoes' => 'Resoluções',
    'relatorios' => 'Relatórios',
    'secoes-administrativas' => 'Seções Administrativas'
]);
define('FOLDERS_SCHEMA', ['projetos_de_extensao']);


function formatSize($sizeInBytes) {
    $sizeInKB = $sizeInBytes / 1024;
    return number_format($sizeInKB, 0, thousands_separator:'') . ' kB';
}

function scanDirectory($directory, $type) {
    $result = [];
    if($type === 'MULTIPLE')
    {
        $subdirs = array_filter(glob($directory . '/*'), 'is_dir');
        foreach (array_reverse($subdirs) as $subdir) {
            $folderName = basename($subdir);
            $subfolderData = [
                "ano" => $folderName,
                "dados" => []
            ];
            $pdfFiles = glob($subdir . '/*.pdf');
            foreach (array_reverse($pdfFiles) as $pdf) {
                $pdfFileName = basename($pdf);
                $fileSize = filesize($pdf);
                $subfolderData['dados'][] = [
                    "nome" => $pdfFileName,
                    "link" => EM_URL.'/arquivos/'.FM_PATH.'/'.$folderName.'/'.rawurlencode($pdfFileName),
                    "tamanho" => formatSize($fileSize),
                ];
            }
            $result[] = $subfolderData;
        }
    }elseif($type === 'SINGLE')
    {
        $folderName = basename($directory);
        $pdfFiles = glob($directory . '/*.pdf');
        foreach ($pdfFiles as $pdf) {
            $pdfFileName = basename($pdf);
            $fileSize = filesize($pdf);
            $result[] = [
                "nome" => $pdfFileName,
                "link" => EM_URL.'/arquivos/'.FM_PATH.'/'.$folderName.'/'.rawurlencode($pdfFileName),
                "tamanho" => formatSize($fileSize),
            ];
        }
    }
    return $result;
}

function createJsonForDirectory($directory, $type) {
    $folderName = isset(FOLDER_NAMES[FM_PATH]) ? ucfirst(FOLDER_NAMES[FM_PATH]) : ucfirst(FM_PATH); // Primeira letra maiúscula
    $jsonData = [
        "titulo" => $folderName,
        "dados" => scanDirectory($directory, $type)
    ];
    return $jsonData;
}

if (isset($_POST['gz']) && !FM_READONLY) {
    $type = $_POST['gz'];

    try {
        $jsonName = 'data.json';
        $jsonFileUrl = $root_url.'/'.FM_PATH.'/'.$jsonName;
        
        $jsonData =json_encode(createJsonForDirectory($dir, $type), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonData === false) {
            throw new Exception("Erro ao codificar o JSON.");
        }
        
        $jsonFileSave = file_put_contents($jsonFileUrl, $jsonData);
        if($jsonFileSave === false){
            throw new Exception("Erro ao salvar o JSON.");
        }
        
        $gzipFile = $jsonFileUrl.'.gz';
        if (!$gz = gzopen($gzipFile, 'w9')) {
            throw new Exception("Erro ao criar o arquivo $gzipFile.");
        }

        gzwrite($gz, $jsonData);
        gzclose($gz);

        fm_set_msg("Arquivo <b>$jsonName</b> criado com sucesso!", 'ok');
    } catch(Exception $e) {
        fm_set_msg('<b>'.$e->getMessage().'</b>', 'error');
    }
}

?>