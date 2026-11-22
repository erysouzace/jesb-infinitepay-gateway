<?php
/** Dependency-free regression harness; no external requests or real charges. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
class WP_Error { private $code; public function __construct($c,$m="",$d=null){$this->code=$c;} public function get_error_code(){return $this->code;} public function get_error_message(){return $this->code;} }
class WC_Order_Item_Product { public function __construct(public $product){} public function get_product(){return $this->product;} }
class WC_Product { public function __construct(private $virtual, private $downloadable){} public function is_virtual(){return $this->virtual;} public function is_downloadable(){return $this->downloadable;} }
class WC_Order {
    public $meta=[];public $status='pending';public $items=[];public $total=10;
    public function get_id(){return 42;} public function get_payment_method(){return 'jesb_infinitepay';}
    public function get_meta($key,$single=true){return $this->meta[$key]??'';}
    public function update_meta_data($key,$val){$this->meta[$key]=$val;} public function delete_meta_data($key){unset($this->meta[$key]);}
    public function save(){} public function is_paid(){return in_array($this->status,['processing','completed'],true);}
    public function has_status($statuses){return in_array($this->status,$statuses,true);} public function get_currency(){return 'BRL';}
    public function get_total(){return $this->total;} public function payment_complete($tx){$this->status='processing';$this->meta['transaction']=$tx;}
    public function get_items($kind='line_item'){return $this->items;} public function update_status($s){$this->status=$s;}
}
class WC_Payment_Gateway {
    public function init_settings(){}public function get_option($k,$default=''){return $default;}
    public function get_return_url($order){return 'https://site.example/order/42';}
    public function is_available(){return true;}
}
class WP_REST_Request { public function __construct(private $data=[]){} public function get_body(){return json_encode($this->data);} public function get_json_params(){return $this->data;} } class WC_Admin_Settings {public static function add_error($s){}}
$GLOBALS['order']=new WC_Order(); $GLOBALS['options']=[]; $GLOBALS['mock_api']=[]; $GLOBALS['notice']=[];
function wc_get_order($id){return $GLOBALS['order'];}
function add_option($key,$value,$unused='',$autoload='no'){if(isset($GLOBALS['options'][$key]))return false;$GLOBALS['options'][$key]=$value;return true;}
function get_option($key,$default=null){return $GLOBALS['options'][$key]??$default;}
function delete_option($key){unset($GLOBALS['options'][$key]);}
function absint($v){return abs((int)$v);}function wp_generate_password($n,$a=false,$b=false){return str_repeat('x',$n);}
function wc_add_notice($message,$type){$GLOBALS['notice'][]=[$message,$type];}
function esc_html($s){return $s;}function __($s,$domain=''){return $s;}function esc_html__($s,$domain=''){return $s;}
function sanitize_key($v){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',$v));}
function sanitize_text_field($s){return $s;}function esc_url_raw($s){return $s;}function wp_unslash($s){return $s;}
function gmdate_stub(){return '';}
function is_wp_error($v){return $v instanceof WP_Error;}
function wp_parse_url($url){return parse_url($url);}
function add_action(...$args){}function add_filter(...$args){}function rest_url($s){return 'https://site.example/wp-json/'.$s;}
function wc_get_checkout_url(){return 'https://site.example/checkout';}
function get_woocommerce_currency(){return 'BRL';}
function wc_format_decimal($x,$decimal){return number_format((float)$x,$decimal,'.','');}
function wc_get_price_decimals(){return 2;}
function get_bloginfo($x){return 'My shop';}
function wp_strip_all_tags($x){return strip_tags($x);}
function wp_json_encode($v){return json_encode($v);}
function rest_ensure_response($v){return $v;}
function as_schedule_single_action($when,$hook,$args,$group,$unique){$GLOBALS['scheduled'][]=[$when,$hook,$args,$group,$unique];return count($GLOBALS['scheduled']);}
function wp_safe_remote_post($url,$args){return $GLOBALS['mock_response']??['code'=>200,'body'=>'{}'];}
function wp_remote_retrieve_response_code($response){return $response['code'];}
function wp_remote_retrieve_body($response){return $response['body'];}
require __DIR__.'/../includes/class-jesb-ipg-api.php';
require __DIR__.'/../includes/class-jesb-ipg-checkout-data.php';
require __DIR__.'/../includes/class-jesb-ipg-attempts.php';
require __DIR__.'/../includes/class-jesb-ipg-confirmation.php';
require __DIR__.'/../includes/class-jesb-ipg-reconciliation.php';
require __DIR__.'/../includes/class-jesb-ipg-gateway.php';
function check($value,$message){if(!$value){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";}
$order=$GLOBALS['order'];$nsu='jig-42-'.str_repeat('x',24);
JESB_IPG_Attempts::put($order,['nsu'=>$nsu,'amount'=>1000,'expires'=>time()+3600]);
check(JESB_IPG_Attempts::find($order,$nsu)!==null,'Find stored attempt');
check(JESB_IPG_Attempts::find($order,'jig-42-'.str_repeat('z',24))===null,'Reject unknown NSU');
check(JESB_IPG_Confirmation::valid_identifiers($nsu,'transaction-123','invoice_slug'), 'Accept validated identifiers');
check(!JESB_IPG_Confirmation::valid_identifiers($nsu,'../../../','slug'), 'Reject malformed transaction identifiers');
$lock=JESB_IPG_Attempts::lock(42,'create');check($lock!=='','Acquire checkout lock');
check(JESB_IPG_Attempts::lock(42,'create')==='','Reject concurrent checkout');
JESB_IPG_Attempts::unlock($lock);check(JESB_IPG_Attempts::lock(42,'create')!=='','Release checkout lock');
$completed=JESB_IPG_Confirmation::completion_status('processing',42,$order);
check($completed==='processing','Empty cart not completed');
$order->items=[new WC_Order_Item_Product(new WC_Product(true,true))];
check(JESB_IPG_Confirmation::completion_status('processing',42,$order)==='completed','Digital-only completes');
$order->items[]=new WC_Order_Item_Product(new WC_Product(false,false));
check(JESB_IPG_Confirmation::completion_status('processing',42,$order)==='processing','Mixed order processes');
$order->items=[];$order->meta['_jesb_ipg_order_nsu']=$nsu;$order->meta['_jesb_ipg_amount_cents']=1000;
$order->meta['_jesb_ipg_payment_url']='https://checkout.infinitepay.io/example';
JESB_IPG_Attempts::put($order,['nsu'=>$nsu,'amount'=>1000,'expires'=>time()-30]);
$gateway=new JESB_IPG_Gateway();
// Testing the blocked renewal path without contacting InfinitePay.
$reflect=new ReflectionMethod(JESB_IPG_Gateway::class,'create_or_reuse_payment');
$result=$reflect->invoke($gateway,$order,1000);
check($result['result']==='failure' && $order->get_meta('_jesb_ipg_attention_required')==='yes','Expired link cannot silently renew');
$order->meta['_jesb_ipg_payment_url']='https://checkout.infinitepay.io/example';
JESB_IPG_Attempts::put($order,['nsu'=>$nsu,'amount'=>1000,'expires'=>time()+3600]);
$result=$reflect->invoke($gateway,$order,1000);
check($result['result']==='success' && $result['redirect']===$order->meta['_jesb_ipg_payment_url'],'Valid link is reused');
$order->meta['_jesb_ipg_payment_url']='';
$result=$reflect->invoke($gateway,$order,1500);
check($result['result']==='failure','Changed order total cannot create second invoice');
check(JESB_IPG_Reconciliation::queue($order,$nsu,'transaction-123','invoice_slug')===true,'Schedule verify with official IDs');
check(JESB_IPG_Reconciliation::queue($order,$nsu,'transaction-123','invoice_slug',6)===false,'Retry budget enforced');
// Verified payment must match amount and may never be confirmed from a webhook alone.
$order->meta['_jesb_ipg_payment_url']='https://checkout.infinitepay.io/example';
$GLOBALS['mock_response']=['code'=>200,'body'=>json_encode(['success'=>true,'paid'=>true,'amount'=>900,'capture_method'=>'pix'])];
$bad=JESB_IPG_Confirmation::verify($order,['order_nsu'=>$nsu,'transaction_nsu'=>'tx-good','slug'=>'slug-good']);
check(is_wp_error($bad)&&$bad->get_error_code()==='jesb_ipg_amount'&&!$order->is_paid(),'Reject confirmed payment with wrong amount');
$GLOBALS['mock_response']=['code'=>200,'body'=>json_encode(['success'=>true,'paid'=>false,'amount'=>1000])];
$unpaid=JESB_IPG_Confirmation::verify($order,['order_nsu'=>$nsu,'transaction_nsu'=>'tx-good','slug'=>'slug-good']);
check(is_wp_error($unpaid)&&!$order->is_paid(),'Unpaid response cannot complete order');
$GLOBALS['mock_response']=['code'=>200,'body'=>json_encode(['success'=>true,'paid'=>true,'amount'=>1000,'capture_method'=>'pix'])];
$good=JESB_IPG_Confirmation::verify($order,['order_nsu'=>$nsu,'transaction_nsu'=>'tx-good','slug'=>'slug-good']);
check($good===true&&$order->is_paid(),'Officially verified matching payment completes order');
$again=JESB_IPG_Confirmation::verify($order,['order_nsu'=>$nsu,'transaction_nsu'=>'tx-good','slug'=>'slug-good']);
check($again===true,'Duplicate verified callback is idempotent');
$conflict=JESB_IPG_Confirmation::verify($order,['order_nsu'=>$nsu,'transaction_nsu'=>'tx-another','slug'=>'slug-good']);
check(is_wp_error($conflict)&&$conflict->get_error_code()==='jesb_ipg_transaction_conflict','Reject conflicting transaction after payment');
$order->status='pending';
$order->meta['_jesb_ipg_confirmed_transaction']='';
$GLOBALS['mock_response']=['code'=>200,'body'=>json_encode(['success'=>true,'paid'=>false,'amount'=>1000])];
$webhook=JESB_IPG_Confirmation::webhook(new WP_REST_Request(['order_nsu'=>$nsu,'transaction_nsu'=>'tx-not-paid','invoice_slug'=>'invoice_slug']));
check(!$order->is_paid() && is_array($webhook) && !empty($webhook['queued']),'Unsigned webhook does not complete unpaid order');
$forged=JESB_IPG_Confirmation::webhook(new WP_REST_Request(['order_nsu'=>'jig-42-'.str_repeat('z',24),'transaction_nsu'=>'tx-fake','invoice_slug'=>'invoice_slug']));
check(is_wp_error($forged) && !$order->is_paid(),'Unknown attempt webhook is rejected');
echo "All mock-based tests passed.\n";
