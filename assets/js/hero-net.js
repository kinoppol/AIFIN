// Animated node network for the landing hero (ported from the design prototype).
(function () {
  var cv = document.getElementById('hero-net');
  if (!cv) return;
  var ctx = cv.getContext('2d');
  var W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
  var N = 34, nodes = [], links = [], pulses = [], raf;
  var rnd = function (a, b) { return a + Math.random() * (b - a); };
  var core = { x: 0, y: 0 };

  function build() {
    nodes = [core]; links = [];
    core.x = W * 0.5; core.y = H * 0.5;
    for (var i = 0; i < N; i++)
      nodes.push({ x: rnd(0, W), y: rnd(0, H), vx: rnd(-.009, .009), vy: rnd(-.0065, .0065), r: rnd(1.2, 2.6) });
    for (var a = 1; a < nodes.length; a++) {
      if (Math.random() < .45) links.push([0, a]);
      for (var b = a + 1; b < nodes.length; b++) if (Math.random() < .05) links.push([a, b]);
    }
  }
  function resize() {
    var r = cv.getBoundingClientRect(); W = r.width; H = r.height;
    cv.width = W * dpr; cv.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    build();
  }
  function spawn() {
    if (!links.length) return;
    var l = links[(Math.random() * links.length) | 0], d = Math.random() < .5;
    pulses.push({ a: d ? l[0] : l[1], b: d ? l[1] : l[0], t: 0, sp: rnd(.00025, .00065) });
  }
  function frame() {
    raf = requestAnimationFrame(frame);
    if (!cv.isConnected) { cancelAnimationFrame(raf); return; }
    ctx.clearRect(0, 0, W, H);
    for (var i = 1; i < nodes.length; i++) {
      var n = nodes[i]; n.x += n.vx; n.y += n.vy;
      if (n.x < 0 || n.x > W) n.vx *= -1;
      if (n.y < 0 || n.y > H) n.vy *= -1;
    }
    ctx.lineWidth = 1;
    for (var k = 0; k < links.length; k++) {
      var p = nodes[links[k][0]], q = nodes[links[k][1]];
      var dist = Math.hypot(p.x - q.x, p.y - q.y), alpha = Math.max(0, .3 - dist / 1900);
      if (alpha <= 0) continue;
      ctx.strokeStyle = 'rgba(140,180,255,' + alpha.toFixed(3) + ')';
      ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
    }
    if (Math.random() < .012) spawn();
    for (var j = pulses.length - 1; j >= 0; j--) {
      var pu = pulses[j]; pu.t += pu.sp;
      if (pu.t >= 1) { pulses.splice(j, 1); continue; }
      var pp = nodes[pu.a], qq = nodes[pu.b];
      var x = pp.x + (qq.x - pp.x) * pu.t, y = pp.y + (qq.y - pp.y) * pu.t, fade = Math.sin(pu.t * Math.PI);
      var g = ctx.createRadialGradient(x, y, 0, x, y, 9);
      g.addColorStop(0, 'rgba(120,255,225,' + (.85 * fade).toFixed(3) + ')');
      g.addColorStop(1, 'rgba(0,179,154,0)');
      ctx.fillStyle = g; ctx.beginPath(); ctx.arc(x, y, 9, 0, 6.2832); ctx.fill();
      ctx.fillStyle = 'rgba(220,255,250,' + fade.toFixed(3) + ')';
      ctx.beginPath(); ctx.arc(x, y, 1.7, 0, 6.2832); ctx.fill();
    }
    for (var m = 1; m < nodes.length; m++) {
      ctx.fillStyle = 'rgba(180,210,255,.55)';
      ctx.beginPath(); ctx.arc(nodes[m].x, nodes[m].y, nodes[m].r, 0, 6.2832); ctx.fill();
    }
    var pr = 16 + Math.sin(performance.now() / 1000 * 0.08) * 3;
    var cg = ctx.createRadialGradient(core.x, core.y, 0, core.x, core.y, pr * 3.4);
    cg.addColorStop(0, 'rgba(47,109,255,.55)'); cg.addColorStop(1, 'rgba(47,109,255,0)');
    ctx.fillStyle = cg; ctx.beginPath(); ctx.arc(core.x, core.y, pr * 3.4, 0, 6.2832); ctx.fill();
    ctx.strokeStyle = 'rgba(160,200,255,.45)'; ctx.lineWidth = 1.2;
    ctx.beginPath(); ctx.arc(core.x, core.y, pr, 0, 6.2832); ctx.stroke();
    ctx.fillStyle = 'rgba(235,245,255,.92)';
    ctx.beginPath(); ctx.arc(core.x, core.y, 4.2, 0, 6.2832); ctx.fill();
  }
  resize();
  new ResizeObserver(resize).observe(cv);
  frame();
})();
