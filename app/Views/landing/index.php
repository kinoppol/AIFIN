<?php
/** @var array $packages */
$unitDays = (int) config('app.unit_days', 30);
$loggedIn = App\Core\Auth::check();
$isAdmin  = App\Core\Auth::isAdmin();
$primaryHref = $loggedIn ? ($isAdmin ? url('admin') : url('account')) : url('register');
?>
<div style="min-height:100vh;background:var(--bg)">

  <!-- nav -->
  <div style="position:sticky;top:0;z-index:30;backdrop-filter:blur(14px);background:color-mix(in srgb,var(--bg) 78%,transparent);border-bottom:1px solid var(--border)">
    <div style="max-width:1180px;margin:0 auto;padding:14px 28px;display:flex;align-items:center;gap:28px">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="logo"></span>
        <span style="font-weight:700;letter-spacing:-.02em;font-size:16px">AIPRO&thinsp;Contracts</span>
      </div>
      <div style="display:flex;gap:22px;font-size:14px;color:var(--muted);margin-left:8px" class="nav-links">
        <a href="#how" style="color:inherit">วิธีการทำงาน</a>
        <a href="#rules" style="color:inherit">กติกาสัญญา</a>
        <a href="#pricing" style="color:inherit">ราคา</a>
        <a href="#faq" style="color:inherit">คำถามที่พบบ่อย</a>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
        <button type="button" data-theme-toggle class="btn btn-ghost btn-sm"><span data-theme-label>ตามระบบ</span></button>
        <?php if ($loggedIn): ?>
          <a class="btn btn-primary btn-sm" href="<?= e($primaryHref) ?>"><?= $isAdmin ? 'แผงผู้ดูแล' : 'บัญชีของฉัน' ?></a>
        <?php else: ?>
          <a class="btn btn-light btn-sm" href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a>
          <a class="btn btn-primary btn-sm" href="<?= e(url('register')) ?>">ลงทะเบียน</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- hero -->
  <div style="position:relative;overflow:hidden;background:linear-gradient(180deg,var(--navy1),var(--navy) 60%,var(--navy1));color:#eaf1ff">
    <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.05) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(120% 90% at 50% 0%,#000 20%,transparent 75%)"></div>
    <div style="position:absolute;top:-180px;left:50%;transform:translateX(-50%);width:900px;height:520px;background:radial-gradient(closest-side,rgba(47,109,255,.45),transparent);filter:blur(20px);animation:sweep 7s ease-in-out infinite"></div>
    <canvas id="hero-net" style="position:absolute;inset:0;width:100%;height:100%;display:block;opacity:.85;mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent)"></canvas>
    <div class="hero-grid" style="position:relative;max-width:1180px;margin:0 auto;padding:96px 28px 88px;display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center">
      <div>
        <div style="display:inline-flex;align-items:center;gap:9px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.06);border-radius:99px;padding:6px 13px;font-size:12.5px;color:#b8ccff">
          <span style="width:6px;height:6px;border-radius:99px;background:var(--accent2)"></span>
          1 หน่วย (M) = สิทธิ์ AI แบบ Pro <?= $unitDays ?> วัน — เสมอ
        </div>
        <h1 style="margin:20px 0 0;font-size:52px;line-height:1.12;font-weight:700;letter-spacing:-.03em">ซื้อสิทธิ์ AI Pro<br>ล่วงหน้าเป็น<span style="background:linear-gradient(100deg,var(--accent),var(--accent2));-webkit-background-clip:text;background-clip:text;color:transparent">สัญญา</span></h1>
        <p style="margin:20px 0 0;max-width:520px;font-size:16.5px;line-height:1.7;color:#a9bcd8">ล็อกราคาวันนี้ เก็บหน่วย AI Pro ไว้ในบัญชีของคุณ แล้วค่อยแลกเป็นสิทธิ์ใช้งานจริงเมื่อพร้อม — สัญญาอายุ 1 ปี ขอขยายเพิ่มได้สูงสุด 6 เดือน</p>
        <div style="display:flex;gap:12px;margin-top:30px;flex-wrap:wrap">
          <a class="btn" style="background:#fff;color:#0d1c34;padding:14px 24px;font-size:15px" href="<?= e($primaryHref) ?>">เปิดบัญชีสัญญา</a>
          <a class="btn" style="border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.06);color:#eaf1ff;padding:14px 24px;font-size:15px" href="#pricing">ดูตารางราคา</a>
        </div>
        <div style="display:flex;gap:40px;margin-top:44px;flex-wrap:wrap">
          <div><div class="mono" style="font-size:26px;font-weight:600">1 ปี</div><div style="font-size:12.5px;color:#8ba1c4;margin-top:4px">อายุสัญญามาตรฐาน</div></div>
          <div><div class="mono" style="font-size:26px;font-weight:600">+6 ด.</div><div style="font-size:12.5px;color:#8ba1c4;margin-top:4px">ขยายได้สูงสุด</div></div>
          <div><div class="mono" style="font-size:26px;font-weight:600"><?= $unitDays ?> วัน</div><div style="font-size:12.5px;color:#8ba1c4;margin-top:4px">ต่อ 1 หน่วย M</div></div>
        </div>
      </div>
      <div style="animation:floaty 9s ease-in-out infinite">
        <div style="border:1px solid rgba(255,255,255,.14);background:rgba(9,18,34,.72);border-radius:18px;padding:22px;box-shadow:0 30px 70px rgba(0,0,0,.45)">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12.5px;color:#8ba1c4;letter-spacing:.06em">คลังหน่วยของบัญชี</span>
            <span class="mono" style="font-size:11px;color:#b8ccff;border:1px solid rgba(255,255,255,.18);padding:3px 8px;border-radius:6px">CT-2026-0148</span>
          </div>
          <div style="display:flex;align-items:baseline;gap:10px;margin-top:14px">
            <span class="mono" style="font-size:46px;font-weight:600">144</span>
            <span style="font-size:18px;color:#8ba1c4">M คงเหลือ</span>
          </div>
          <div style="height:8px;border-radius:99px;background:rgba(255,255,255,.12);overflow:hidden;margin-top:14px">
            <div style="height:100%;width:60%;border-radius:99px;background:linear-gradient(90deg,var(--accent),var(--accent2))"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:#8ba1c4;margin-top:8px"><span>แลกไปแล้ว 96 M</span><span>ทั้งหมด 240 M</span></div>
          <div style="height:1px;background:rgba(255,255,255,.12);margin:18px 0"></div>
          <div style="display:grid;gap:10px">
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:#8ba1c4">แลก 12 M → ops@sinnovate.co</span><span style="color:var(--accent2)">สำเร็จ</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:#8ba1c4">แลก 6 M → data@sinnovate.co</span><span style="color:#e2b23c">กำลังจัดหาสิทธิ์</span></div>
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:#8ba1c4">สัญญาหมดอายุ</span><span>11 ม.ค. 2027</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- how -->
  <div id="how" style="max-width:1180px;margin:0 auto;padding:80px 28px 20px">
    <div class="mono" style="font-size:12.5px;color:var(--accent);letter-spacing:.14em">HOW IT WORKS</div>
    <h2 style="margin:12px 0 0;font-size:34px">จากสัญญา สู่สิทธิ์ใช้งานจริง</h2>
    <div class="how-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:36px">
      <?php
      $steps = [
        ['01', 'ทำสัญญา & ชำระเงิน', 'เลือกจำนวนหน่วยที่ต้องการซื้อล่วงหน้า ระบบออกสัญญาอายุ 1 ปีให้อัตโนมัติ'],
        ['02', 'รับหน่วย AI Pro (M)', 'หน่วยเข้าคลังของบัญชีทันทีหลังชำระเงิน ยังไม่เริ่มนับเวลาใช้งาน'],
        ['03', 'แลกหน่วย + ผูกอีเมล', "ระบุอีเมลที่จะใช้งาน แล้วนำหน่วยมาแลกเป็นสิทธิ์ {$unitDays} วันต่อหน่วย"],
        ['04', 'ผู้จำหน่ายจัดหาสิทธิ์', 'ผู้จำหน่ายซื้อสิทธิ์ AI จริงตามจำนวนที่แลก และผูกเข้าอีเมลที่ระบุ'],
      ];
      foreach ($steps as [$n, $h, $p]): ?>
        <div class="card card-pad">
          <div class="mono" style="font-size:13px;color:var(--faint)"><?= $n ?></div>
          <div style="font-weight:600;font-size:17px;margin-top:10px"><?= e($h) ?></div>
          <p class="muted" style="margin:8px 0 0;font-size:14px;line-height:1.65"><?= e($p) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- rules -->
  <div id="rules" style="max-width:1180px;margin:0 auto;padding:56px 28px">
    <div class="rules-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px">
      <div class="card-navy" style="padding:26px">
        <div class="mono" style="font-size:30px;font-weight:600">1 M = <?= $unitDays ?> วัน</div>
        <p style="margin:10px 0 0;font-size:14px;line-height:1.7;color:#a9bcd8">ราคาต่อหน่วยอาจปรับตามโปรโมชั่น แต่มูลค่าการใช้งานของ 1 หน่วยคงที่เสมอ</p>
      </div>
      <div class="card card-pad" style="padding:26px">
        <div class="mono" style="font-size:30px;font-weight:600;color:var(--accent)">12 เดือน</div>
        <p class="muted" style="margin:10px 0 0;font-size:14px;line-height:1.7">หน่วยที่ยังไม่ถูกแลกจะอยู่ในคลังได้ตลอดอายุสัญญา 1 ปีนับจากวันเริ่มสัญญา</p>
      </div>
      <div class="card card-pad" style="padding:26px">
        <div class="mono" style="font-size:30px;font-weight:600;color:var(--accent2)">+ 6 เดือน</div>
        <p class="muted" style="margin:10px 0 0;font-size:14px;line-height:1.7">ยื่นคำขอขยายอายุสัญญาได้ รวมแล้วไม่เกิน 6 เดือนต่อหนึ่งสัญญา</p>
      </div>
    </div>
  </div>

  <!-- pricing -->
  <div id="pricing" style="max-width:1180px;margin:0 auto;padding:44px 28px 20px">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap">
      <div>
        <div class="mono" style="font-size:12.5px;color:var(--accent);letter-spacing:.14em">PRICING</div>
        <h2 style="margin:12px 0 0;font-size:34px">ซื้อมาก ราคาต่อหน่วยถูกลง</h2>
      </div>
      <div class="muted" style="font-size:13px;max-width:320px;text-align:right">ราคาต่อหน่วยเป็นราคา ณ วันทำสัญญา และถูกล็อกไว้ตลอดอายุสัญญา</div>
    </div>
    <div class="price-grid" style="display:grid;grid-template-columns:repeat(<?= max(1, min(4, count($packages))) ?>,1fr);gap:18px;margin-top:32px">
      <?php foreach ($packages as $i => $p):
        $featured = ($i === 1);
        $total = (int) $p['units'] * (int) $p['sale_price'];
        $days = (int) $p['units'] * $unitDays;
      ?>
        <div style="position:relative;border-radius:16px;padding:24px;box-shadow:var(--shadow);
             <?= $featured
                ? 'border:1px solid transparent;background:linear-gradient(160deg,var(--navy),var(--navy1));color:#eaf1ff'
                : 'border:1px solid var(--border);background:var(--surface);color:var(--text)' ?>">
          <?php if (!empty($p['promo_label'])): ?>
            <div style="position:absolute;top:14px;right:14px;font:600 11px var(--sans);background:var(--accent2);color:#03251f;border-radius:6px;padding:4px 8px"><?= e($p['promo_label']) ?></div>
          <?php endif; ?>
          <div style="font-weight:600;font-size:16px"><?= e($p['name']) ?></div>
          <div class="mono" style="font-size:40px;font-weight:600;margin-top:14px"><?= e($p['units']) ?></div>
          <div style="font-size:13px;opacity:.7;margin-top:2px">หน่วย (M)</div>
          <div style="height:1px;background:currentColor;opacity:.12;margin:18px 0"></div>
          <div style="font-size:15px;font-weight:600"><?= baht($total) ?></div>
          <div style="font-size:13px;opacity:.7;margin-top:4px"><?= baht($p['sale_price']) ?> ต่อหน่วย · <?= number_format($days) ?> วันใช้งาน</div>
          <a class="btn btn-block" style="margin-top:20px;<?= $featured ? 'background:#fff;color:#0d1c34' : 'background:var(--sunk);color:var(--text);border:1px solid var(--border)' ?>" href="<?= e($primaryHref) ?>">เริ่มทำสัญญา</a>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="faint" style="margin-top:18px;font-size:13px">* ราคายังไม่รวม VAT · องค์กรที่ต้องการเกิน 500 M ติดต่อฝ่ายขายเพื่อทำสัญญาเฉพาะ</div>
  </div>

  <!-- faq -->
  <div id="faq" style="max-width:1180px;margin:0 auto;padding:64px 28px 90px">
    <h2 style="margin:0 0 26px;font-size:28px">คำถามที่พบบ่อย</h2>
    <div class="faq-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
      <?php
      $faqs = [
        ['หน่วยที่ซื้อหมดอายุไหม?', 'หน่วยจะอยู่ในคลังได้จนถึงวันสิ้นสุดสัญญา (1 ปี) หากขอขยายอายุสัญญาได้รับอนุมัติ หน่วยจะยืดตามไปด้วยสูงสุด 6 เดือน'],
        ['เปลี่ยนอีเมลที่ผูกได้หรือไม่?', 'อีเมลจะผูกตอนแลกหน่วยเท่านั้น สิทธิ์ที่จัดหาแล้วย้ายอีเมลไม่ได้ แต่หน่วยที่ยังไม่แลกนำไปผูกอีเมลอื่นได้อิสระ'],
        ['ราคาลดช่วงโปรฯ กระทบสัญญาเดิมไหม?', 'ไม่กระทบ ราคาใช้ ณ ตอนซื้อหน่วยเท่านั้น มูลค่าการใช้งานของหน่วยคือ ' . $unitDays . ' วันเสมอไม่ว่าซื้อมาราคาใด'],
        ['แลกแล้วนานแค่ไหนถึงใช้ได้?', 'คำขอแลกจะเข้าคิวจัดหาสิทธิ์ โดยทั่วไปผูกบัญชีเสร็จภายใน 1 วันทำการ และเริ่มนับ ' . $unitDays . ' วันเมื่อสิทธิ์ถูกเปิดใช้'],
      ];
      foreach ($faqs as [$q, $a]): ?>
        <div class="card" style="padding:20px 22px">
          <div style="font-weight:600;font-size:15px"><?= e($q) ?></div>
          <p class="muted" style="margin:8px 0 0;font-size:14px;line-height:1.7"><?= e($a) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="border-top:1px solid var(--border);background:var(--sunk)">
    <div style="max-width:1180px;margin:0 auto;padding:28px;display:flex;justify-content:space-between;align-items:center;gap:20px;font-size:13px;color:var(--muted);flex-wrap:wrap">
      <span>© <?= date('Y') ?> AIPRO Contracts · ระบบสัญญาซื้อขายสิทธิ์ AI ล่วงหน้า</span>
      <div style="display:flex;gap:20px"><a href="#rules">เงื่อนไขสัญญา</a><a href="#pricing">ราคา</a><a href="#how">ติดต่อฝ่ายขาย</a></div>
    </div>
  </div>
</div>
<style>
@media (max-width:900px){
  .hero-grid{grid-template-columns:1fr!important}
  .how-grid{grid-template-columns:repeat(2,1fr)!important}
  .rules-grid,.price-grid,.faq-grid{grid-template-columns:1fr!important}
  .nav-links{display:none!important}
}
</style>
<script src="<?= e(asset('js/hero-net.js')) ?>"></script>
