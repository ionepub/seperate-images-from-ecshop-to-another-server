<?php
/**
 * 将图片或文件上传到图片服务器
 */
class CustomRemoteFile
{
    //配置
    var $cus_remote_file_target = "http://images.site.com/uploadFile.php";  //服务器地址
    var $cus_remote_file_key = "";                                          //access_key
    
    /**
     * 上传百度编辑器图片
     */
    function cus_remote_editor_file($cus_remote_file_path){
        $cus_remote_file_re = $this->curl_post_contents($this->cus_remote_file_target, array('file'=>'@'.realpath($cus_remote_file_path), 'dir'=>dirname($cus_remote_file_path), 'access_key'=>$this->cus_remote_file_key, 'promote_type'=>'ueditor'));
        if($cus_remote_file_re == "SUCCESS"){
            return str_replace('../../../', '/', $cus_remote_file_path);
        }else{
            return "http://".$_SERVER['HTTP_HOST']."/includes/ueditor/php/".$cus_remote_file_path;
        }
    }

    /**
     * 上传商品编辑中的相册
     */
    function cus_remote_gallery_file($cus_remote_file_path){
        $data = array(
            'file' => '@'.realpath($cus_remote_file_path),
            'dir'  => dirname($cus_remote_file_path),
            'access_key'=>$this->cus_remote_file_key, 
            'promote_type'=>'gallery',
        );
        return $this->curl_post_contents($this->cus_remote_file_target, $data);
    }

    /**
     * curl 发送post数据和文件
     */
    function curl_post_contents($url, $postdata) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }
}

    



    
?>
