<?php
namespace application\controllers;
use Exception;

class ApiController extends Controller {
    public function categoryList() {
        return $this->model->getCategoryList();
    }

    public function productInsert() {
        $json = getJson();
        return [_RESULT => $this->model->productInsert($json)];
    }

    public function productList2(){
        $result = $this->model->productList2();
        return $result === false ? [] : $result;
    }

    public function productDetail(){
        $urlPaths = getUrlPaths();
        if(!isset($urlPaths[2])){
            exit();
        }
        $param = [
            'product_id' => intval($urlPaths[2])
        ];
        return $this->model->productDetail($param);
    }
    /*public function upload(){
        $urlPaths = getUrlPaths();
        if(!isset($urlPaths[2])|| !isset($urlPaths[3])){
            exit();
        }
        $productId = intval($urlPaths[2]);
        $type = intval($urlPaths[3]);
        $json = getJson();
        $image_parts = explode(";base64,",$json["imgae"]);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $dirPath = _IMG_PATH . "/" . $productId . "/" . $type;
        $filePath = $dirPath . "/" . uniqid() . "." . $image_type;
        if(!is_dir($dirPath)){
            mkdir($dirPath, 0777, true);
        }
        $result = file_put_contents($filePath, $image_base64);
        return [_RESULT => 1];
    }*/

    public function upload() {
        $urlPaths = getUrlPaths();
        if(!isset($urlPaths[2]) || !isset($urlPaths[3])) {
            exit();
        }
        $productId = intval($urlPaths[2]);
        $type = intval($urlPaths[3]);
        $json = getJson(); //배열형
        $image_parts = explode(";base64,", $json["image"]); //[0]파일명 및 타입 ;base 65 [1]이미지 로 나눔
        $image_type_aux = explode("image/", $image_parts[0]);  //[0]데이터 [1]파일확장자 로 나눔
        $image_type = $image_type_aux[1];      
        $image_base64 = base64_decode($image_parts[1]); //$image_parts[1] 이미지를 디코딩
        $dirPath = _IMG_PATH . "/" . $productId . "/" . $type;
        $filePath = $dirPath . "/" . uniqid() . "." . $image_type; 
        if(!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $filename = explode("/", $filePath);
        //$file = _IMG_PATH . "/" . $productId . "/" . $type . "/" . uniqid() . "." . $image_type;
        //$file = "static/" . uniqid() . "." . $image_type;
        $result = file_put_contents($filePath, $image_base64);
        if($result){
            $param = [
                'product_id' => $productId,
                'type' => $type,
                'path' => $filename[4],
            ];
            return $this->model->productImageInsert($param);
        }
        
        return [_RESULT => $result ? 1 : 0];
    }

    public function productImageList(){
        $urlPaths = getUrlPaths();
        if(!isset($urlPaths[2])) {
            exit();
        }
        $productId = intval($urlPaths[2]);
        $param = [
            'product_id' => $productId
        ];

        return $this->model->productImageList($param);
        
    }

    public function productImageDelete(){
        $urlPaths = getUrlPaths();
        if(count($urlPaths) !== 6) { //length 값 비교하여 다 넘어오지 않으면 exit
            exit();
        }
        switch(getMethod()){
            case _DELETE:
                //이미지 파일 삭제
                $delPath = _IMG_PATH . "/$urlPaths[3]/$urlPaths[4]/$urlPaths[5]";
                if(unlink($delPath)){
                //sql에서 삭제
                $param = ["product_image_id" => intval($urlPaths[2])];
                $result = $this->model->productImageDelete($param);
                }
                break;
        }
        return [_RESULT => $result];
    }

    public function deleteProduct(){
        $urlPaths = getUrlPaths();
        if(count($urlPaths) !== 3) { //length 값 비교하여 다 넘어오지 않으면 exit
            exit();
        }
        switch(getMethod()){
            case _DELETE:
                //이미지삭제
                $dir = _IMG_PATH ."/". $urlPaths[2];
                if(is_dir($dir)){
                    rmdirAll($dir);
                }

                //SQL삭제
                $productId = intval($urlPaths[2]);
                $param = ["product_id" => $productId];
                try{
                    $this->model->beginTransaction(); //오토커밋(자동으로 해당 sql문을 적용(INSERT, UPDATE, DELETE))을 끔(mysql은 오토커밋)
                    //ex. ctrl+shift+f9만 누르면 바로 적용되는거, 오토커밋을 끄면 이후 commit문을 다시 작성하여 적용 해줘야됨 
                    $this->model->productImageDelete($param);
                    $result = $this->model->productDelete($param);
                    if($result === 1){
                        $this->model->commit();
                    } else {
                        $this->model->rollback(); //에러가 터지면 기존 상태로 돌림
                    }
                } catch(Exception $e){
                    print "에러발생<br>";
                    print $e . "<br>";
                    $this->model->rollback();
                }
                
                
                //
                
                
                break;
        }
        return [_RESULT => $result];
    }
}