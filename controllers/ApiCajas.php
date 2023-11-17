<?php 
    namespace Controllers;

use Model\Caja;
use Model\Cuota;
use Model\Venta;
use Model\Usuario;

    class ApiCajas {
        public static function cajas(){
            $cajas = Caja::all();

            $i=0;
            $datoJson = '{
             "data": [';
                 foreach($cajas as $key=>$caja){
                    $usuario = Usuario::where('id', $caja->vendedor_id);
                 
                    $i++;

                    $acciones = "<div class='d-flex justify-content-center' >";
                    $acciones .="<button data-caja-id ='".$caja->id."' id='info'  type='button' class='btn btn-sm bg-hover-azul mx-2 text-white toolMio'><span class='toolMio-text'>Info</span><i class='fas fa-search'></i></button>";
                    if($caja->numero_transacciones==0){
                        $acciones .="<button data-caja-id ='".$caja->id."' id='editar'  type='button' class='btn btn-sm bg-hover-azul mx-2 text-white toolMio'><span class='toolMio-text'>Editar</span><i class='fas fa-pen'></i></button>";
                        $acciones .="<button data-caja-id ='".$caja->id."' id='eliminar'  type='button' class='btn btn-sm bg-hover-azul mx-2 text-white toolMio' ><span class='toolMio-text'>Eliminar</span><i class='fas fa-trash' ></i></button>";
                    }
                   
                  
                    $acciones .="</div>";
 
                  
 
                    $estado = '';
                     if($caja->estado == 0){
                       
                        $estado = "<div class='d-flex justify-content-center'>";
                  
                        $estado .= "<button type='button' data-caja-id ='".$caja->id."' id='cerrar'  class='btn w-75 btn-inline bg-azul text-white btn-sm toolMio' style='min-width:70px'><span class='toolMio-text'>Cerrar</span>Abierta</button>";
                        $estado .= "</div >";
                     }else{
                        $estado = "<div class='d-flex justify-content-center' >";
                        $estado .= "<button  type='button' class='btn  w-75 btn-inline btn-secondary btn-sm ' style='min-width:70px'>Cerrada</button>";
                        $estado .= "</div >";
                     }

                     $datoJson.= '[
                             "'.$i.'",
                             "'.$usuario->nombre.'",
                             "$'.number_format($caja->efectivo_apertura).'",
                             "'.number_format($caja->efectivo_cierre).'",
                          
                          
                             "'.$estado.'",
     
                             "'.$acciones.'"
                     ]';
                     if($key != count($cajas)-1){
                         $datoJson.=",";
                     }
                 }
       
             $datoJson.=  ']}';
            echo $datoJson;
            
        }
        public static function caja(){
            $id = $_GET['id'];
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if(!$id){
                echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, Intenta Nuevamente']);
                return;
            }

            $caja = Caja::find($id);
            echo json_encode($caja->efectivo_apertura);
            
        }
      
        public static function crear(){

            session_start();
            date_default_timezone_set('America/Bogota');

       
            $caja_anterior = Caja::get('1');
            
            if($caja_anterior){
                if($caja_anterior->estado == 0){
                    echo json_encode(['type'=>'error', 'msg'=>'Ya hay una caja abierta']);
                    return;
                }
            }
            
            $caja = new Caja($_POST);
            $caja->fecha_apertura = date('Y-m-d H:i:s');
            $caja->vendedor_id = $_SESSION['id'];
            $caja->formatearDatosFloat();
        

   
            $resultado = $caja->guardar();
            if($resultado){
                echo json_encode(['type'=>'success', 'msg'=>'La caja ha sido abierta exitosamente']);
                return;
            }
            echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, porfavor intente nuevamente']);
            return;
        }
        public static function editar(){
            $id = $_POST['id'];
            $id = filter_var($id, FILTER_VALIDATE_INT);
            if(!$id){
                echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, Intenta Nuevamente']);
                return;
            }

            $caja = Caja::find($id);
            if(!$caja){
                echo json_encode(['type'=>'error', 'msg'=>'Hay un Problema con la caja']);
                return;
            }
            $caja->sincronizar($_POST);
            $caja->formatearDatosFloat();
            $resultado = $caja->guardar();
            if($resultado){
                echo json_encode(['type'=>'success', 'msg'=>'La Caja ha sido Actualizado con Exito']);
                return;
            }
            echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, Intenta nuevamente']);
            return;
            
        }
        public static function eliminar(){
            $id = $_POST['id'];
            if(!$id){
                echo json_encode(['type'=>'error', 'msg'=>'Hubo un error Intenta Nuevamente']);
                return;
            }
            $caja = Caja::find($_POST['id']);
            if(!$caja){
                echo json_encode(['type'=>'error', 'msg'=>'Hay un Problema con la Caja']);
                return;
            }
            $resultado = $caja->eliminar();
            if($resultado['status']){
                echo json_encode(['type'=>'success', 'msg'=>'La Caja ha sido Eliminada con Exito']);
                return;
            }
           
         
            echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, Intenta nuevamente']);
            return;
            
            
        }  
        public static function cerrar(){
            date_default_timezone_set('America/Bogota');
            $id = $_POST['id'];
            if(!$id){
                echo json_encode(['type'=>'error', 'msg'=>'Hubo un error Intenta Nuevamente']);
                return;
            }
            $caja = Caja::find($_POST['id']);
            if(!$caja){
                echo json_encode(['type'=>'error', 'msg'=>'Hay un Problema con la Caja']);
                return;
            }

            $ventas = Venta::whereArray(['caja_id'=>$caja->id]);
            $total_ventas  = 0;
            if($ventas){
                foreach($ventas as $venta){
                    if($venta->metodo_pago==1){
                        $total_ventas = $total_ventas + $venta->total;
                    }
                 
                }
            }
           
            $cuotas = Cuota::whereArray(['caja_id'=>$caja->id]);
            if($cuotas){
                foreach($cuotas as $cuota){
                    $total_ventas = $total_ventas + $cuota->monto;
                }  
            }

            $caja->recaudo_ventas = $total_ventas;
            $caja->fecha_cierre = date('Y-m-d H:i:s');
            $caja->estado = 1;
            $caja->efectivo_cierre = $caja->efectivo_apertura+$caja->recaudo_ventas;
            $resultado = $caja->guardar();
            if($resultado){
                echo json_encode(['type'=>'success', 'msg'=>'La Caja ha sido Cerrada con Exito']);
                return;
            }
           
         
            echo json_encode(['type'=>'error', 'msg'=>'Hubo un error, Intenta nuevamente']);
            return;
            
            
        }  
    }