<?php
/**
 * Created by PhpStorm.
 * User: JohnHenrySpike
 * Date: 21.11.2017
 * Time: 16:25
 */
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use common\behaviors\AccessBehavior;
use yii\filters\VerbFilter;
use yii\web\HeaderCollection;

class ImportController extends Controller
{
    public function behaviors() //TODO: доступы к странице
    {
        return [
            /*'as AccessBehavior' => [
                'class' => AccessBehavior::className(),
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],*/
        ];
    }
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction('Upload');
    }
    public function actionIndex()
    {
        //$this->layout = 'main';
        return $this->render('index');
    }

    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;
        if (Yii::$app->user->isGuest) {
            $this->layout = 'empty';
        }
        if ($exception !== null) {
            return $this->render('error', ['exception' => $exception]);
        }
    }

    public function actionStep2($hash = false){
        echo 'file upload success';
    }
    public function actionAbort($hash = false){
        echo 'file upload error';
    }

    public function actionSimple($hash = false){
        echo 'browser not support!';
    }

    public function actionUpload($action = null, $filename = ''){

    // Каталог в который будет загружаться файл
        $uploaddir=Yii::getAlias('@static/files');
        $hash=$_SERVER["HTTP_UPLOAD_ID"];// Идентификатор загрузки (аплоада). Для генерации идентификатора я обычно использую функцию md5()
        $real_name = $_FILES;
        if (true)
            //preg_match("/^[0123456789abcdef]{32}$/i",$hash)) // Проверим корректность идентификатора
        {
            if (Yii::$app->request->isGet) { // Если HTTP запрос сделан методом GET, то это не загрузка порции, а пост-обработка
                if ($action=="abort") { // abort - сотрем загружаемый файл. Загрузка не удалась.
                    if (is_file($uploaddir."/".$hash.".html5upload")) unlink($uploaddir."/".$hash.".html5upload");
                    print "ok abort";
                    return;
                }

                if ($action=="done") { // done - загрузка завершена успешно. Переименуем файл и создадим файл-флаг.
                    // Если файл существует, то удалим его
                    if (is_file($uploaddir."/".$hash.".original")) unlink($uploaddir."/".$hash.".original");

                    // Переименуем загружаемый файл
                    $realname = substr ($filename, strrpos($filename,'\\'));

                    rename($uploaddir."/".$hash.".html5upload",$uploaddir."/".$realname);
                    // Создадим файл-флаг
                    $fw=fopen($uploaddir."/".$hash.".original_ready","wb");if ($fw) fclose($fw);
                }
            }

            elseif (Yii::$app->request->isPost) { // Если HTTP запрос сделан методом POST, то это загрузка порции
                echo 'REQUEST_POST';
                $filename=$uploaddir."/".$hash.".html5upload"; // Имя файла получим из идентификатора загрузки
                // Если загружается первая порция, то откроем файл для записи, если не первая, то для дозаписи.
                if (intval($_SERVER["HTTP_PORTION_FROM"])==0)
                    $fout=fopen($filename,"wb");
                else
                    $fout=fopen($filename,"ab");

                // Если не смогли открыть файл на запись, то выдаем сообщение об ошибке
                if (!$fout) {
                    header("HTTP/1.0 500 Internal Server Error");
                    print "Can't open file for writing.";
                    return;
                }
                // Из stdin читаем данные отправленные методом POST - это и есть содержимое порций
                $fin = fopen("php://input", "rb");
                if ($fin) {
                    while (!feof($fin)) {
                        // Считаем 1Мб из stdin
                        $data=fread($fin, 1024*1024);
                        // Сохраним считанные данные в файл
                        fwrite($fout,$data);
                    }
                    fclose($fin);
                }

                fclose($fout);
            }
            // Все нормально, вернем HTTP 200 и тело ответа "ok"
            header("HTTP/1.0 200 OK");
            print "ok\n";
        }
        else {
            header("HTTP/1.0 500 Internal Server Error");
            print "Wrong session hash.";
        }
    }
    public function actionImport($file=null, $ftell = null, $record = null){
        $JSresponce = ['response'=>'', 'data'=>''];
        $file_name = Yii::getAlias('@static/files/').$file;
        //$JSresponce['DATA']=$file_name;
        $JSresponce['filename']=$file;
        if (($handle_f = fopen($file_name, "r")) !== FALSE)
        {
            // проверяется, надо ли продолжать импорт с определенного места
            // если да, то указатель перемещается на это место
            if($ftell !== null){
                fseek($handle_f,$ftell);
            }
            if ($ftell == 0){
                $tmpfile = file($file_name);
                $JSresponce['FILESIZE'] = count($tmpfile);
                unset ($tmpfile);
            }
            $i=0;
            if($record !== null){
                $x=$record;
            } else {
                $x = 0;
            }

            // построчное считывание и анализ строк из файла
            $insert_q ='';
            while ( ($data_f = fgetcsv($handle_f, 500, ";"))!== FALSE) {
                if(!empty($data_f)){
                    //some code here....
                    $insert_q .='INSERT INTO gis_abonent (account, address) VALUES ('
                        .'\''.$data_f[2].'\','
                        .'\''.$data_f[1].'\''
                        .');\n';
                    $JSresponce['data'] = 'Importing record #: '.$x;
                    $JSresponce['dev'] = $data_f;
                    flush();
                    ob_flush();
                }

                if($i==10000){
                    $JSresponce['response'] = 'importing';
                    $JSresponce['ftell'] = ftell($handle_f);
                    $JSresponce['record'] = $x;
                    //print '<meta http-equiv="Refresh" content="0; url='.$_SERVER['PHP_SELF'].'?x='.$x.'&amp;ftell='.ftell($handle_f).'&amp;path='.$_GET['path'].'">';
                    break;
                }
                $x++;
                $i++;
                $JSresponce['response'] = 'success';
                $JSresponce['ftell'] = ftell($handle_f);
                $JSresponce['record'] = $x;

            }
            fclose($handle_f);

        } else {
            $err = 1;
            $JSresponce['response'] = 'error';
            $JSresponce['data'] = 'File do not open';
        }

        echo json_encode($JSresponce);
    }
}
