<?php
/**
 * Created by PhpStorm.
 * User: JohnHenrySpike
 * Date: 21.11.2017
 * Time: 16:36
 */
$hash=htmlspecialchars(stripslashes($_GET["hash"]));
//$hash=md5("test");
$this->title = Yii::t('app', 'Upload');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Import'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style type="text/css">
    #cnuploader_progressbar {
        margin-top:10px;
        height:16px;
        font-family:sans-serif;
        font-size:12px;
        padding:3px;
        width:300px;
        position:absolute;
        text-align:center;
        color:black;
        border:1px solid black;
        display:none;
    }
    #cnuploader_progresscomplete {
        margin-top:10px;
        height:16px;
        font-family:sans-serif;
        font-size:12px;
        padding:3px;
        width:0;
        text-align:center;
        background-color:blue;
        color:white;
        border:1px solid transparent;
        display:none;}
</style>
<div class="abonents-import">
    <div class="row">
        <div class="col">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Uploaded files</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <p>Table of uploaded files</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Upload form</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <form action="./" method="post" id="uploadform" onsubmit="return false;" style="display:none;">

                        <div id="message">Выберите файл:</div><input type="file" id="files" name="files[]" />

                        <input type="submit" value="Загрузить &gt;&gt;" />
                    </form>
                    <div id="cnuploader_progressbar"></div>
                    <div id="cnuploader_progresscomplete"></div>
                    <input type="button" class="import" onclick="ImportFile('test.csv')" value="Import"/>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    // Для начала определим метод XMLHttpRequest.sendAsBinary(),
    // если он не определен (Например, для браузера Google Chrome).
    if (!XMLHttpRequest.prototype.sendAsBinary) {
        XMLHttpRequest.prototype.sendAsBinary = function(datastr) {
            function byteValue(x) {
                return x.charCodeAt(0) & 0xff;
            }
            var ords = Array.prototype.map.call(datastr, byteValue);
            var ui8a = new Uint8Array(ords);
            this.send(ui8a.buffer);
        }
    }

    /**
     * Класс FileUploader.
     * @param ioptions Ассоциативный массив опций загрузки
     */
    function FileUploader(ioptions) {
        this.position=0; // Позиция, с которой будем загружать файл
        this.filesize=0; // Размер загружаемого файла
        this.file = null;  // Объект Blob или File (FileList[i])
        this.options=ioptions;  // Ассоциативный массив опций
        // Если не определена опция uploadscript, то возвращаем null. Нельзя
        // продолжать, если эта опция не определена.
        if (this.options['uploadscript']==undefined) return null;
        /* Проверка, поддерживает ли браузер необходимые объекты
        * @return true, если браузер поддерживает все необходимые объекты*/

        this.CheckBrowser=function() {
            if (window.File && window.FileReader && window.FileList && window.Blob) return true; else return false;
        }
        /*
        * Загрузка части файла на сервер
        * @param from Позиция, с которой будем загружать файл
        */
        this.UploadPortion=function(from) {
            var reader = new FileReader();
            var that=this;
            var loadfrom=from;
            var blob=null;
            // Таймаут для функции setTimeout. С помощью этой функции реализована повторная попытка загрузки
            // по таймауту (что не совсем корректно)
            var xhrHttpTimeout=null;
            /*
            * Событие срабатывающее после чтения части файла в FileReader
            * @param evt Событие
            */
            reader.onloadend = function(evt) {
                if (evt.target.readyState === FileReader.DONE) {
                    params = {}
                    // Создадим объект XMLHttpRequest, установим адрес скрипта для POST
                    // и необходимые заголовки HTTP запроса.
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', that.options['uploadscript'], true);
                    xhr.setRequestHeader("Content-Type", "application/x-binary; charset=x-user-defined");
                    xhr.setRequestHeader("Upload-Id", that.options['uploadid']);  // Идентификатор загрузки (чтобы знать на стороне сервера что с чем склеивать)
                    xhr.setRequestHeader("Portion-From", from);// Позиция начала в файле
                    xhr.setRequestHeader("Portion-Size", that.options['portion']);// Размер порции
                    xhr.setRequestHeader("X-CSRF-TOKEN", yii.getCsrfToken());
                    that.xhrHttpTimeout=setTimeout(function() {// Установим таймаут
                        xhr.abort();
                    },that.options['timeout']);

                    /*
                    * Событие XMLHttpRequest.onProcess. Отрисовка ProgressBar.
                    * @param evt Событие
                    */
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            // Посчитаем количество закаченного в процентах (с точность до 0.1)
                            var percentComplete = Math.round((loadfrom+evt.loaded) * 1000 / that.filesize);percentComplete/=10;
                            // Посчитаем ширину синей полоски ProgressBar
                            var width=Math.round((loadfrom+evt.loaded) * 300 / that.filesize);
                            // Изменим свойства элементом ProgressBar'а, добавим к нему текст
                            var div1=document.getElementById('cnuploader_progressbar');
                            var div2=document.getElementById('cnuploader_progresscomplete');

                            div1.style.display='block';
                            div2.style.display='block';
                            div2.style.width=width+'px';
                            if (percentComplete<30) {
                                div2.textContent='';
                                div1.textContent=percentComplete+'%';
                            }
                            else {
                                div2.textContent=percentComplete+'%';
                                div1.textContent='';
                            }
                        }
                    }, false);

                    /*
                    * Событие XMLHttpRequest.onLoad. Окончание загрузки порции.
                    * @param evt Событие
                    */
                    xhr.addEventListener("load", function(evt) {
                        // Очистим таймаут
                        clearTimeout(that.xhrHttpTimeout);
                        // Если сервер не вернул HTTP статус 200, то выведем окно с сообщением сервера.
                        if (evt.target.status!=200) {
                            //alert(evt.target.responseText);
                            console.log('error');
                            return;
                        }
                        // Добавим к текущей позиции размер порции.
                        that.position+=that.options['portion'];

                        // Закачаем следующую порцию, если файл еще не кончился.
                        if (that.filesize>that.position) {
                            that.UploadPortion(that.position);
                        }
                        else {
                            // Если все порции загружены, сообщим об этом серверу. XMLHttpRequest, метод GET,
                            // PHP скрипт тот-же.
                            var gxhr = new XMLHttpRequest();
                            gxhr.open('GET', that.options['uploadscript']+'?action=done'+'&filename='+document.getElementById('files').value, true);
                            // Установим идентификатор загруки.

                            gxhr.setRequestHeader("Upload-Id", that.options['uploadid']);
                            /*
                            * Событие XMLHttpRequest.onLoad. Окончание загрузки сообщения об окончании загрузки файла :).
                            * @param evt Событие
                            */
                            gxhr.addEventListener("load", function(evt) {

                                // Если сервер не вернул HTTP статус 200, то выведем окно с сообщением сервера.
                                if (evt.target.status!=200) {
                                    alert(evt.target.responseText.toString());
                                    console.log('error');
                                    return;
                                }
                                // Если все нормально, то отправим пользователя дальше. Там может быть сообщение
                                // об успешной загрузке или следующий шаг формы с дополнительным полями.
                                else window.parent.location=that.options['redirect_success'];
                            }, false);

                            // Отправим HTTP GET запрос
                            gxhr.sendAsBinary('');
                        }
                    }, false);

                    /*
                    * Событие XMLHttpRequest.onError. Ошибка при загрузке
                    * @param evt Событие
                    */
                    xhr.addEventListener("error", function(evt) {

                        // Очистим таймаут
                        clearTimeout(that.xhrHttpTimeout);

                        // Сообщим серверу об ошибке во время загруке, сервер сможет удалить уже загруженные части.
                        // XMLHttpRequest, метод GET,  PHP скрипт тот-же.
                        var gxhr = new XMLHttpRequest();

                        gxhr.open('GET', that.options['uploadscript']+'?action=abort', true);

                        // Установим идентификатор загруки.
                        gxhr.setRequestHeader("Upload-Id", that.options['uploadid']);

                        /*
                        * Событие XMLHttpRequest.onLoad. Окончание загрузки сообщения об ошибке загрузки :).
                        * @param evt Событие
                        */
                        gxhr.addEventListener("load", function(evt) {

                            // Если сервер не вернул HTTP статус 200, то выведем окно с сообщением сервера.
                            if (evt.target.status!=200) {
                                alert(evt.target.responseText);
                                console.log('error2');
                                return;
                            }
                        }, false);

                        // Отправим HTTP GET запрос
                        gxhr.sendAsBinary('');

                        // Отобразим сообщение об ошибке
                        if (that.options['message_error']==undefined) alert("There was an error attempting to upload the file."); else alert(that.options['message_error']);
                    }, false);

                    /*
                    * Событие XMLHttpRequest.onAbort. Если по какой-то причине передача прервана, повторим попытку.
                    * @param evt Событие
                    */
                    xhr.addEventListener("abort", function(evt) {
                        clearTimeout(that.xhrHttpTimeout);
                        that.UploadPortion(that.position);
                    }, false);

                    // Отправим порцию методом POST
                    xhr.sendAsBinary(evt.target.result);
                }
            };

            that.blob=null;

            // Считаем порцию в объект Blob. Три условия для трех возможных определений Blob.[.*]slice().
            if (this.file.slice) that.blob=this.file.slice(from,from+that.options['portion']);
           /* else {
                if (this.file.webkitSlice) that.blob=this.file.webkitSlice(from,from+that.options['portion']);
                else {
                    if (this.file.mozSlice) that.blob=this.file.mozSlice(from,from+that.options['portion']);
                }
            }*/

            // Считаем Blob (часть файла) в FileReader
            reader.readAsBinaryString(that.blob);
        }


        /*
        * Загрузка файла на сервер
        * return Число. Если не 0, то произошла ошибка
        */
        this.Upload=function() {

            // Скроем форму, чтобы пользователь не отправил файл дважды
            var e=document.getElementById(this.options['form']);
            if (e) e.style.display='none';

            if (!this.file) return -1;
            else {
                // Если размер файла больше размера порциии ограничимся одной порцией
                if (this.filesize>this.options['portion']) this.UploadPortion(0,this.options['portion']);

                // Иначе отправим файл целиком
                else this.UploadPortion(0,this.filesize);
            }
        }
        if (this.CheckBrowser()) {

            // Установим значения по умолчанию
            if (this.options['portion']==undefined) this.options['portion']=1048576;
            if (this.options['timeout']==undefined) this.options['timeout']=15000;

            var that = this;

            // Добавим обработку события выбора файла
            document.getElementById(this.options['formfiles']).addEventListener('change', function (evt) {

                var files=evt.target.files;

                // Выберем только первый файл
                for (var i = 0, f; f = files[i]; i++) {
                    that.filesize=f.size;
                    that.file = f;
                    break;
                }
            }, false);

            // Добавим обработку события onSubmit формы
            document.getElementById(this.options['form']).addEventListener('submit', function (evt) {

                that.Upload();
                (arguments[0].preventDefault)? arguments[0].preventDefault(): arguments[0].returnValue = false;
            }, false);
        }


    }
</script>
<script>
    function ImportFile (filename, ftell=0, record=0){
        //console.log(ftell + '  ' + record);
        $.ajax({
            type:'GET',
            url:'/import/import',
            dataType:'json',
            data: {
                file:filename,
                ftell:ftell,
                record:record
            },
            success:function(data){
                var importRspns = data.response;
                console.log(data);
                //console.log('data.ftell='+data.ftell);
                //console.log('data.record='+data.record);
                switch (importRspns){
                    case 'importing': ImportFile(data.filename, data.ftell, data.record); break;
                    case 'success':console.log(importRspns);break;
                    case 'error':console.log(importRspns);break;
                    default:console.log(importRspns);break;
                }
            }
        });
    }
</script>
<script>

    function ShowForm() {

        // Создаем объект - FileUploader. Задаем опции.
        var uploader=new FileUploader( {
            message_error: 'Ошибка при загрузке файла',
            form: 'uploadform', //uploadformid
            formfiles: 'files', // ID элемента <input type=file
            uploadid: '<?php print $hash;?>',// Идентификатор загрузки. В нашему случе хэш.
            uploadscript: '/import/upload/', // URL скрипта загрузки
            redirect_success: '/import/step2/?hash=<?php print $hash;?>',
            redirect_abort: '/import/abort/?hash=<?php print $hash;?>',
            // Размер порции. 2 Мб
            portion: 1024*1024*2
        });

        // Если не удалось создать объект, то перенаправим пользователя на простую форму загруки.
        if (!uploader) document.location='/import/simple/?hash=<?php print $hash;?>';
        else {
            // Если браузер не поддерживается, то перенаправим пользователя на простую форму загруки.
            if (!uploader.CheckBrowser()) document.location='/import/simple/?hash=<?php print $hash;?>';
            else {
                // Если все нормально, то отобразим форму (по умолчанию она скрыта)
                var e=document.getElementById('uploadform');
                if (e) e.style.display='block';

            }
        }
    }
    ShowForm();
</script>
