<?php
/**
 * Plugin Name: WooCommerce User Purchase Statistics
 * Description: نمایش آمار خرید کاربران WooCommerce در قالب نمودارهای گرافیکی با شورت‌کد [woo_statics]
 * Version: 1.3.0
 * Author: Alireza Fatemi
 * Author URI: https://alirezafatemi.ir
 * Plugin URI: https://github.com/deveguru
 * Text Domain: woo-statics
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
 exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
 add_action('admin_notices', 'woo_statics_woocommerce_missing_notice');
 return;
}

function woo_statics_woocommerce_missing_notice() {
 echo '<div class="error"><p><strong>WooCommerce User Purchase Statistics:</strong> این افزونه نیاز به فعال بودن WooCommerce دارد.</p></div>';
}

class WooStatics {
 
 private $version = '1.3.0';
 
 public function __construct() {
 add_action('init', array($this, 'init'));
 }
 
 public function init() {
 add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
 add_shortcode('woo_statics', array($this, 'render_shortcode'));
 add_shortcode('woo_preview', array($this, 'render_preview_shortcode'));
 add_action('admin_menu', array($this, 'add_admin_menu'));
 }
 
 public function enqueue_scripts() {
 wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js', array(), '4.4.0', true);
 wp_enqueue_style('font-awesome-statics', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
 wp_add_inline_style('wp-block-library', $this->get_custom_styles());
 }
 
 private function get_custom_styles() {
 return "
 .woo-statics-container { max-width: 1200px; margin: 20px auto; padding: 20px; background: #EEEEEE; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
 .woo-statics-header { text-align: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #00CED1, #10B7EF); border-radius: 10px; color: white; }
 .woo-statics-title { font-size: 28px; font-weight: bold; margin: 0 0 10px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
 .woo-statics-subtitle { font-size: 16px; opacity: 0.9; margin: 0; }
 .woo-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
 .woo-stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 5px solid #00CED1; transition: transform 0.3s ease; }
 .woo-stat-card:hover { transform: translateY(-5px); }
 .woo-stat-card:nth-child(2) { border-left-color: #606060; }
 .woo-stat-card:nth-child(3) { border-left-color: #10B7EF; }
 .woo-stat-card:nth-child(4) { border-left-color: #000000; }
 .woo-stat-number { font-size: 32px; font-weight: bold; color: #000000; margin-bottom: 5px; }
 .woo-stat-label { color: #606060; font-size: 14px; font-weight: 500; }
 .woo-charts-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
 .woo-chart-wrapper { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
 .woo-chart-title { font-size: 20px; font-weight: bold; color: #000000; margin-bottom: 20px; text-align: center; padding-bottom: 10px; border-bottom: 2px solid #00CED1; }
 .woo-chart-canvas { position: relative; height: 400px; width: 100%; }
 .woo-products-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
 .woo-table-header { background: linear-gradient(135deg, #606060, #000000); color: white; padding: 20px; font-size: 18px; font-weight: bold; text-align: center; }
 .woo-products-list { max-height: 400px; overflow-y: auto; }
 .woo-product-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #EEEEEE; transition: background-color 0.3s ease; }
 .woo-product-item:hover { background-color: #f8f9fa; }
 .woo-product-name { font-weight: 500; color: #000000; flex: 1; }
 .woo-product-stats { display: flex; gap: 15px; }
 .woo-product-stat { text-align: center; }
 .woo-product-stat-number { font-weight: bold; color: #00CED1; display: block; }
 .woo-product-stat-label { font-size: 12px; color: #606060; }
 .woo-no-data { text-align: center; padding: 60px 20px; color: #606060; }
 .woo-no-data-icon { font-size: 48px; margin-bottom: 20px; color: #00CED1; }
 .woo-login-notice { background: linear-gradient(135deg, #10B7EF, #00CED1); color: white; padding: 30px; text-align: center; border-radius: 15px; margin: 20px 0; }
 .woo-login-button { display: inline-block; background: white; color: #10B7EF; padding: 12px 24px; text-decoration: none; border-radius: 25px; font-weight: bold; margin-top: 15px; transition: transform 0.3s ease; }
 .woo-login-button:hover { transform: scale(1.05); color: #00CED1; }
 .woo-preview-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
 .woo-preview-card.is-toggle { display: flex; flex-direction: column; justify-content: center; align-items: center; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-bottom: 4px solid #00CED1; cursor: pointer; position: relative; transition: all 0.3s ease; }
 .woo-preview-accordion-item:last-child .woo-preview-card.is-toggle { border-bottom-color: #10B7EF; }
 .woo-preview-card.is-toggle .fas { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; transition: transform 0.3s ease; }
 .woo-preview-card.is-toggle.active .fas { transform: translateY(-50%) rotate(180deg); }
 .woo-preview-number { font-size: 2.5rem; font-weight: 700; color: #000000; }
 .woo-preview-label { font-size: 1rem; color: #606060; margin-top: 5px; }
 .woo-preview-details-content { max-height: 0; overflow: hidden; transition: max-height 0.5s ease-in-out; background: #fafafa; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; margin-top: -5px; }
 .woo-preview-details-content.active { max-height: 1000px; padding: 15px; }
 .woo-preview-product-item { display: grid; grid-template-columns: 1fr auto auto; gap: 10px; padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; align-items: center; }
 .woo-preview-product-item:last-child { border-bottom: none; }
 .woo-preview-product-name { font-weight: 500; text-decoration: none; color: #333; transition: color 0.2s ease; }
 .woo-preview-product-name:hover { color: #00CED1; }
 .woo-preview-product-qty { color: #666; }
 .woo-preview-product-date { font-size: 12px; color: #888; }
 .woo-preview-pagination { display: flex; justify-content: center; align-items: center; gap: 5px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
 .woo-preview-pagination button { background: #00CED1; color: white; border: none; border-radius: 5px; padding: 5px 10px; font-size: 12px; cursor: pointer; transition: background-color 0.2s; }
 .woo-preview-pagination button:hover { background: #00B7B7; }
 .woo-preview-pagination button:disabled { background: #ccc; cursor: not-allowed; }
 .woo-preview-pagination .page-num { font-weight: 600; padding: 5px; }
 @media (max-width: 1024px) { .woo-statics-container { margin: 15px; padding: 18px; } .woo-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; } .woo-charts-container { gap: 20px; } .woo-chart-wrapper { padding: 20px; } .woo-chart-title { font-size: 18px; } }
 @media (max-width: 768px) { .woo-statics-container { margin: 10px; padding: 15px; } .woo-statics-title { font-size: 24px; } .woo-statics-subtitle { font-size: 14px; } .woo-statics-header { padding: 15px; margin-bottom: 20px; } .woo-stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; } .woo-stat-card { padding: 15px; } .woo-stat-number { font-size: 24px; } .woo-stat-label { font-size: 12px; } .woo-charts-container { grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px; } .woo-chart-wrapper { padding: 15px; } .woo-chart-title { font-size: 16px; margin-bottom: 15px; } .woo-chart-canvas { height: 280px; } .woo-product-item { flex-direction: column; align-items: flex-start; gap: 10px; padding: 12px 15px; } .woo-product-name { font-size: 14px; } .woo-product-stats { width: 100%; justify-content: space-around; gap: 10px; } .woo-product-stat-number { font-size: 13px; } .woo-product-stat-label { font-size: 11px; } .woo-table-header { padding: 15px; font-size: 16px; } .woo-products-list { max-height: 300px; } }
 @media (max-width: 480px) { .woo-statics-container { margin: 5px; padding: 12px; } .woo-statics-title { font-size: 20px; } .woo-statics-subtitle { font-size: 13px; } .woo-statics-header { padding: 12px; margin-bottom: 15px; } .woo-stats-grid { grid-template-columns: 1fr; gap: 10px; margin-bottom: 15px; } .woo-stat-card { padding: 12px; } .woo-stat-number { font-size: 20px; } .woo-stat-label { font-size: 11px; } .woo-chart-wrapper { padding: 12px; } .woo-chart-title { font-size: 14px; margin-bottom: 12px; } .woo-chart-canvas { height: 220px; } .woo-product-item { padding: 10px 12px; } .woo-product-name { font-size: 13px; margin-bottom: 8px; } .woo-product-stats { flex-wrap: wrap; gap: 8px; } .woo-product-stat { flex: 1; min-width: 60px; } .woo-product-stat-number { font-size: 12px; } .woo-product-stat-label { font-size: 10px; } .woo-table-header { padding: 12px; font-size: 14px; } .woo-products-list { max-height: 250px; } .woo-login-notice { padding: 20px; } .woo-login-button { padding: 10px 20px; font-size: 14px; } .woo-preview-container { grid-template-columns: 1fr; } }
 @media (max-width: 320px) { .woo-statics-container { margin: 3px; padding: 10px; } .woo-statics-title { font-size: 18px; } .woo-chart-canvas { height: 180px; } .woo-stat-number { font-size: 18px; } .woo-product-stats { flex-direction: column; align-items: center; gap: 5px; } .woo-product-stat { min-width: auto; } }
 ";
 }
 
 public function render_shortcode($atts) {
 $atts = shortcode_atts(array('user_id' => get_current_user_id(), 'limit' => -1), $atts, 'woo_statics');
 if (!is_user_logged_in()) { return $this->render_login_notice(); }
 $user_id = intval($atts['user_id']);
 $user_data = get_userdata($user_id);
 if (!$user_data) { return '<div class="woo-statics-container"><p>کاربر یافت نشد.</p></div>'; }
 $purchase_data = $this->get_user_purchase_data($user_id, $atts['limit']);
 if (empty($purchase_data['products'])) { return $this->render_no_data($user_data->display_name); }
 return $this->render_statistics($purchase_data, $user_data->display_name);
 }
 
 private function convert_to_jalali($date_obj) {
 if (!class_exists('IntlDateFormatter') || !$date_obj) { return $date_obj ? $date_obj->format('Y-m-d') : ''; }
 $datetime = new DateTime($date_obj->date('Y-m-d H:i:s'), new DateTimeZone('UTC'));
 $datetime->setTimezone(new DateTimeZone('Asia/Tehran'));
 $formatter = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'Asia/Tehran', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
 return $formatter->format($datetime);
 }

 public function render_preview_shortcode($atts) {
 if (!is_user_logged_in()) { return $this->render_login_notice(); }
 $user_id = get_current_user_id();
 $orders = wc_get_orders(array('customer_id' => $user_id, 'status' => array('wc-completed', 'wc-processing'), 'limit' => -1, 'orderby' => 'date', 'order' => 'DESC'));
 
 $completed_count = 0;
 $processing_count = 0;
 $completed_products = [];
 $processing_products = [];
 
 foreach($orders as $order){
 $view_order_url = $order->get_view_order_url();
 $order_items = [];
 foreach($order->get_items() as $item){
 $product = $item->get_product();
 if($product){
 $order_items[] = ['name' => $product->get_name(), 'qty' => $item->get_quantity(), 'date' => $this->convert_to_jalali($order->get_date_created()), 'url' => $view_order_url];
 }
 }
 if($order->get_status() === 'completed'){
 $completed_count++;
 $completed_products = array_merge($completed_products, $order_items);
 } elseif($order->get_status() === 'processing'){
 $processing_count++;
 $processing_products = array_merge($processing_products, $order_items);
 }
 }
 
 $unique_id = 'ws-preview-' . uniqid();
 ob_start();
 ?>
 <div id="<?php echo esc_attr($unique_id); ?>" class="woo-preview-wrapper-main">
 <div class="woo-preview-container">
 <div class="woo-preview-accordion-item">
 <div class="woo-preview-card is-toggle">
 <div class="woo-preview-number"><?php echo number_format($completed_count); ?></div>
 <div class="woo-preview-label">سفارشات تکمیل شده</div>
 <?php if (!empty($completed_products)): ?><i class="fas fa-chevron-down"></i><?php endif; ?>
 </div>
 <?php if (!empty($completed_products)): ?>
 <div class="woo-preview-details-content">
 <div class="woo-preview-product-list">
 <?php foreach ($completed_products as $product): ?>
 <div class="woo-preview-product-item">
 <a href="<?php echo esc_url($product['url']); ?>" class="woo-preview-product-name"><?php echo esc_html($product['name']); ?></a>
 <span class="woo-preview-product-qty">تعداد: <?php echo esc_html($product['qty']); ?></span>
 <span class="woo-preview-product-date"><?php echo esc_html($product['date']); ?></span>
 </div>
 <?php endforeach; ?>
 </div>
 <div class="woo-preview-pagination"></div>
 </div>
 <?php endif; ?>
 </div>
 
 <div class="woo-preview-accordion-item">
 <div class="woo-preview-card is-toggle">
 <div class="woo-preview-number"><?php echo number_format($processing_count); ?></div>
 <div class="woo-preview-label">سفارشات در حال ارسال</div>
 <?php if (!empty($processing_products)): ?><i class="fas fa-chevron-down"></i><?php endif; ?>
 </div>
 <?php if (!empty($processing_products)): ?>
 <div class="woo-preview-details-content">
 <div class="woo-preview-product-list">
 <?php foreach ($processing_products as $product): ?>
 <div class="woo-preview-product-item">
 <a href="<?php echo esc_url($product['url']); ?>" class="woo-preview-product-name"><?php echo esc_html($product['name']); ?></a>
 <span class="woo-preview-product-qty">تعداد: <?php echo esc_html($product['qty']); ?></span>
 <span class="woo-preview-product-date"><?php echo esc_html($product['date']); ?></span>
 </div>
 <?php endforeach; ?>
 </div>
 <div class="woo-preview-pagination"></div>
 </div>
 <?php endif; ?>
 </div>
 </div>
 
 <script>
 document.addEventListener('DOMContentLoaded', function() {
 const mainWrapper = document.getElementById('<?php echo esc_js($unique_id); ?>');
 const setupPagination = (listContainer, paginationContainer) => {
 const items = Array.from(listContainer.children);
 const itemsPerPage = 5;
 const totalPages = Math.ceil(items.length / itemsPerPage);
 let currentPage = 1;
 if (totalPages <= 1) return;
 const displayPage = (page) => {
 currentPage = page;
 const start = (page - 1) * itemsPerPage;
 const end = start + itemsPerPage;
 items.forEach((item, index) => {
 item.style.display = (index >= start && index < end) ? 'grid' : 'none';
 });
 updatePaginationButtons();
 };
 const updatePaginationButtons = () => {
 const prevBtn = paginationContainer.querySelector('.prev-btn');
 const nextBtn = paginationContainer.querySelector('.next-btn');
 const pageNum = paginationContainer.querySelector('.page-num');
 if (prevBtn && nextBtn && pageNum) {
 prevBtn.disabled = currentPage === 1;
 nextBtn.disabled = currentPage === totalPages;
 pageNum.textContent = `${currentPage} / ${totalPages}`;
 }
 };
 paginationContainer.innerHTML = `<button class="prev-btn" disabled>&laquo; قبلی</button><span class="page-num">1 / ${totalPages}</span><button class="next-btn">بعدی &raquo;</button>`;
 paginationContainer.querySelector('.prev-btn').addEventListener('click', () => { if (currentPage > 1) displayPage(currentPage - 1); });
 paginationContainer.querySelector('.next-btn').addEventListener('click', () => { if (currentPage < totalPages) displayPage(currentPage + 1); });
 displayPage(1);
 };
 mainWrapper.querySelectorAll('.woo-preview-accordion-item').forEach(item => {
 const toggle = item.querySelector('.is-toggle');
 const content = item.querySelector('.woo-preview-details-content');
 if (toggle && content) {
 toggle.addEventListener('click', () => {
 toggle.classList.toggle('active');
 content.classList.toggle('active');
 });
 const productList = content.querySelector('.woo-preview-product-list');
 const paginationContainer = content.querySelector('.woo-preview-pagination');
 if (productList && paginationContainer) {
 setupPagination(productList, paginationContainer);
 }
 }
 });
 });
 </script>
 </div>
 <?php
 return ob_get_clean();
 }
 
 private function get_user_purchase_data($user_id, $limit = -1) {
 $orders = wc_get_orders(array('customer_id' => $user_id, 'status' => array('wc-completed', 'wc-processing'), 'limit' => $limit, 'orderby' => 'date', 'order' => 'DESC'));
 $products = array(); $total_spent = 0; $total_orders = count($orders); $total_items = 0; $monthly_data = array();
 foreach ($orders as $order) {
 $order_date = $order->get_date_created(); $month_key = $order_date->format('Y-m');
 if (!isset($monthly_data[$month_key])) { $monthly_data[$month_key] = array('total' => 0, 'orders' => 0, 'items' => 0); }
 $monthly_data[$month_key]['total'] += floatval($order->get_total()); $monthly_data[$month_key]['orders']++;
 foreach ($order->get_items() as $item) {
 $product_id = $item->get_product_id(); $product = wc_get_product($product_id); $quantity = $item->get_quantity(); $item_total = floatval($item->get_total());
 $total_items += $quantity; $monthly_data[$month_key]['items'] += $quantity;
 if ($product) {
 $product_name = $product->get_name();
 if (!isset($products[$product_id])) { $products[$product_id] = array('name' => $product_name, 'quantity' => 0, 'total' => 0, 'average_price' => 0, 'orders_count' => 0); }
 $products[$product_id]['quantity'] += $quantity; $products[$product_id]['total'] += $item_total; $products[$product_id]['orders_count']++; $products[$product_id]['average_price'] = $products[$product_id]['total'] / $products[$product_id]['quantity'];
 }
 }
 $total_spent += floatval($order->get_total());
 }
 uasort($products, function($a, $b) { return $b['total'] <=> $a['total']; }); ksort($monthly_data);
 return array('products' => $products, 'total_spent' => $total_spent, 'total_orders' => $total_orders, 'total_items' => $total_items, 'monthly_data' => $monthly_data, 'average_order_value' => $total_orders > 0 ? $total_spent / $total_orders : 0);
 }
 
 private function render_login_notice() {
 $login_url = wp_login_url(get_permalink());
 return '<div class="woo-statics-container"><div class="woo-login-notice"><h3>برای مشاهده آمار خرید خود وارد شوید</h3><p>جهت مشاهده اطلاعات خریدهای خود، لطفاً ابتدا وارد حساب کاربری خود شوید.</p><a href="' . esc_url($login_url) . '" class="woo-login-button">ورود به حساب کاربری</a></div></div>';
 }
 
 private function render_no_data($user_name) {
 return '<div class="woo-statics-container"><div class="woo-statics-header"><h2 class="woo-statics-title">آمار خرید ' . esc_html($user_name) . '</h2></div><div class="woo-no-data"><div class="woo-no-data-icon">🛒</div><h3>هنوز خریدی انجام نداده‌اید</h3><p>پس از انجام اولین خرید، آمار شما در اینجا نمایش داده خواهد شد.</p></div></div>';
 }
 
 private function render_statistics($data, $user_name) {
 $unique_id = uniqid(); ob_start();
 ?>
 <div class="woo-statics-container">
 <div class="woo-statics-header"><h2 class="woo-statics-title">آمار خرید <?php echo esc_html($user_name); ?></h2><p class="woo-statics-subtitle">تحلیل کامل خریدهای شما از فروشگاه</p></div>
 <div class="woo-stats-grid">
 <div class="woo-stat-card"><div class="woo-stat-number"><?php echo number_format($data['total_spent']); ?></div><div class="woo-stat-label">مجموع خرید (تومان)</div></div>
 <div class="woo-stat-card"><div class="woo-stat-number"><?php echo number_format($data['total_orders']); ?></div><div class="woo-stat-label">تعداد سفارشات</div></div>
 <div class="woo-stat-card"><div class="woo-stat-number"><?php echo number_format($data['total_items']); ?></div><div class="woo-stat-label">تعداد کل محصولات</div></div>
 <div class="woo-stat-card"><div class="woo-stat-number"><?php echo number_format($data['average_order_value']); ?></div><div class="woo-stat-label">میانگین هر سفارش (تومان)</div></div>
 </div>
 <div class="woo-charts-container">
 <div class="woo-chart-wrapper"><h3 class="woo-chart-title">مبلغ خرید محصولات</h3><div class="woo-chart-canvas"><canvas id="productAmountChart<?php echo $unique_id; ?>"></canvas></div></div>
 <div class="woo-chart-wrapper"><h3 class="woo-chart-title">تعداد محصولات خریداری شده</h3><div class="woo-chart-canvas"><canvas id="productQuantityChart<?php echo $unique_id; ?>"></canvas></div></div>
 </div>
 <?php if (!empty($data['monthly_data'])): ?>
 <div class="woo-chart-wrapper" style="margin-bottom: 30px;"><h3 class="woo-chart-title">روند خرید ماهانه</h3><div class="woo-chart-canvas"><canvas id="monthlyTrendChart<?php echo $unique_id; ?>"></canvas></div></div>
 <?php endif; ?>
 <div class="woo-products-table">
 <div class="woo-table-header">لیست کامل محصولات خریداری شده</div>
 <div class="woo-products-list">
 <?php foreach ($data['products'] as $product): ?>
 <div class="woo-product-item">
 <div class="woo-product-name"><?php echo esc_html($product['name']); ?></div>
 <div class="woo-product-stats">
 <div class="woo-product-stat"><span class="woo-product-stat-number"><?php echo number_format($product['quantity']); ?></span><span class="woo-product-stat-label">تعداد</span></div>
 <div class="woo-product-stat"><span class="woo-product-stat-number"><?php echo number_format($product['total']); ?></span><span class="woo-product-stat-label">مجموع (تومان)</span></div>
 <div class="woo-product-stat"><span class="woo-product-stat-number"><?php echo number_format($product['average_price']); ?></span><span class="woo-product-stat-label">میانگین قیمت</span></div>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 </div>
 </div>
 <script type="text/javascript">
 document.addEventListener('DOMContentLoaded', function() {
 const colorPalette = ['#00CED1', '#606060', '#000000', '#EEEEEE', '#10B7EF']; const productNames = <?php echo json_encode(array_values(array_column($data['products'], 'name'))); ?>; const productTotals = <?php echo json_encode(array_values(array_column($data['products'], 'total'))); ?>; const productQuantities = <?php echo json_encode(array_values(array_column($data['products'], 'quantity'))); ?>; const isMobile = window.innerWidth <= 768; const isSmallMobile = window.innerWidth <= 480;
 const responsiveOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: isMobile ? 'bottom' : 'bottom', labels: { padding: isMobile ? 10 : 20, usePointStyle: true, font: { size: isSmallMobile ? 10 : isMobile ? 11 : 12, family: 'Segoe UI' }, boxWidth: isSmallMobile ? 10 : 15 } }, tooltip: { titleFont: { size: isSmallMobile ? 11 : 12 }, bodyFont: { size: isSmallMobile ? 10 : 11 }, padding: isSmallMobile ? 8 : 12 } } };
 const ctxAmount = document.getElementById('productAmountChart<?php echo $unique_id; ?>').getContext('2d');
 new Chart(ctxAmount, { type: 'doughnut', data: { labels: productNames.slice(0, isMobile ? 6 : 10), datasets: [{ data: productTotals.slice(0, isMobile ? 6 : 10), backgroundColor: colorPalette, borderColor: '#ffffff', borderWidth: isMobile ? 2 : 3, hoverBorderWidth: isMobile ? 3 : 5 }] }, options: { ...responsiveOptions, plugins: { ...responsiveOptions.plugins, tooltip: { ...responsiveOptions.plugins.tooltip, callbacks: { label: function(context) { return context.label + ': ' + context.parsed.toLocaleString() + ' تومان'; } } } } } });
 const ctxQuantity = document.getElementById('productQuantityChart<?php echo $unique_id; ?>').getContext('2d');
 new Chart(ctxQuantity, { type: 'bar', data: { labels: productNames.slice(0, isMobile ? 6 : 10), datasets: [{ label: 'تعداد', data: productQuantities.slice(0, isMobile ? 6 : 10), backgroundColor: colorPalette[0], borderColor: colorPalette[2], borderWidth: isMobile ? 1 : 2, borderRadius: isMobile ? 4 : 8, borderSkipped: false }] }, options: { ...responsiveOptions, plugins: { legend: { display: false }, tooltip: { ...responsiveOptions.plugins.tooltip, callbacks: { label: function(context) { return 'تعداد: ' + context.parsed.y; } } } }, scales: { y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: isSmallMobile ? 9 : isMobile ? 10 : 11 } } }, x: { grid: { display: false }, ticks: { maxRotation: isMobile ? 90 : 45, font: { size: isSmallMobile ? 8 : isMobile ? 9 : 10 } } } } } });
 <?php if (!empty($data['monthly_data'])): ?>
 const monthlyLabels = <?php echo json_encode(array_keys($data['monthly_data'])); ?>; const monthlyTotals = <?php echo json_encode(array_column($data['monthly_data'], 'total')); ?>; const monthlyOrders = <?php echo json_encode(array_column($data['monthly_data'], 'orders')); ?>;
 const ctxMonthly = document.getElementById('monthlyTrendChart<?php echo $unique_id; ?>').getContext('2d');
 new Chart(ctxMonthly, { type: 'line', data: { labels: monthlyLabels, datasets: [ { label: 'مبلغ خرید (تومان)', data: monthlyTotals, borderColor: colorPalette[0], backgroundColor: colorPalette[0] + '20', borderWidth: isMobile ? 2 : 3, fill: true, tension: 0.4, pointRadius: isMobile ? 3 : 4, pointHoverRadius: isMobile ? 5 : 6, yAxisID: 'y' }, { label: 'تعداد سفارشات', data: monthlyOrders, borderColor: colorPalette[4], backgroundColor: colorPalette[4] + '20', borderWidth: isMobile ? 2 : 3, fill: false, tension: 0.4, pointRadius: isMobile ? 3 : 4, pointHoverRadius: isMobile ? 5 : 6, yAxisID: 'y1' } ] }, options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false, }, plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: isMobile ? 10 : 20, font: { size: isSmallMobile ? 10 : isMobile ? 11 : 12 } } }, tooltip: { titleFont: { size: isSmallMobile ? 11 : 12 }, bodyFont: { size: isSmallMobile ? 10 : 11 } } }, scales: { x: { grid: { display: false }, ticks: { font: { size: isSmallMobile ? 9 : isMobile ? 10 : 11 } } }, y: { type: 'linear', display: true, position: 'left', grid: { color: '#f0f0f0' }, ticks: { font: { size: isSmallMobile ? 9 : isMobile ? 10 : 11 }, callback: function(value) { return value.toLocaleString(); } } }, y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false, }, ticks: { font: { size: isSmallMobile ? 9 : isMobile ? 10 : 11 } } } } } });
 <?php endif; ?>
 });
 </script>
 <?php
 return ob_get_clean();
 }
 
 public function add_admin_menu() {
 add_submenu_page('woocommerce', 'آمار خرید کاربران', 'آمار خرید کاربران', 'manage_woocommerce', 'woo-statics', array($this, 'admin_page'));
 }
 
 public function admin_page() {
 ?>
 <div class="wrap">
 <h1>آمار خرید کاربران WooCommerce</h1>
 <div class="card">
 <h2>نحوه استفاده</h2>
 <p>برای نمایش آمار کامل از شورت‌کد <code>[woo_statics]</code> و برای نمایش خلاصه وضعیت از <code>[woo_preview]</code> استفاده کنید.</p>
 <h3>پارامترهای [woo_statics]:</h3>
 <ul>
 <li><strong>user_id:</strong> شناسه کاربر (پیش‌فرض: کاربر فعلی)</li>
 <li><strong>limit:</strong> محدودیت تعداد سفارشات (پیش‌فرض: همه)</li>
 </ul>
 <p><strong>مثال:</strong> <code>[woo_statics limit="50"]</code></p>
 </div>
 <div class="card">
 <h2>ویژگی‌ها</h2>
 <ul>
 <li>✅ نمایش آمار کلی و خلاصه خرید کاربر</li>
 <li>✅ نمودار دایره‌ای، میله‌ای و خطی</li>
 <li>✅ جدول کامل محصولات خریداری شده</li>
 <li>✅ طراحی کاملاً ریسپانسیو</li>
 <li>✅ پشتیبانی کامل از زبان فارسی</li>
 </ul>
 </div>
 <div class="card">
 <h2>اطلاعات توسعه‌دهنده</h2>
 <p><strong>نویسنده:</strong> Alireza Fatemi</p>
 <p><strong>وب‌سایت:</strong> <a href="https://alirezafatemi.ir" target="_blank">alirezafatemi.ir</a></p>
 <p><strong>گیت‌هاب:</strong> <a href="https://github.com/deveguru" target="_blank">github.com/deveguru</a></p>
 </div>
 </div>
 <?php
 }
}

new WooStatics();

register_activation_hook(__FILE__, 'woo_statics_activate');
function woo_statics_activate() {
 if (version_compare(PHP_VERSION, '7.4', '<')) { wp_die('این افزونه نیاز به PHP نسخه 7.4 یا بالاتر دارد.'); }
 if (!class_exists('WooCommerce')) { wp_die('این افزونه نیاز به نصب و فعال‌سازی WooCommerce دارد.'); }
 flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'woo_statics_deactivate');
function woo_statics_deactivate() {
 flush_rewrite_rules();
}
?>
