var ce = "top",
  be = "bottom",
  ye = "right",
  ue = "left",
  nr = "auto",
  Bt = [ce, be, ye, ue],
  dt = "start",
  Dt = "end",
  ua = "clippingParents",
  mi = "viewport",
  Et = "popper",
  la = "reference",
  Pr = Bt.reduce(function (e, t) {
    return e.concat([t + "-" + dt, t + "-" + Dt]);
  }, []),
  xi = [].concat(Bt, [nr]).reduce(function (e, t) {
    return e.concat([t, t + "-" + dt, t + "-" + Dt]);
  }, []),
  fa = "beforeRead",
  da = "read",
  pa = "afterRead",
  ha = "beforeMain",
  ga = "main",
  va = "afterMain",
  _a = "beforeWrite",
  ba = "write",
  ya = "afterWrite",
  ma = [fa, da, pa, ha, ga, va, _a, ba, ya];
function Me(e) {
  return e ? (e.nodeName || "").toLowerCase() : null;
}
function fe(e) {
  if (e == null) return window;
  if (e.toString() !== "[object Window]") {
    var t = e.ownerDocument;
    return (t && t.defaultView) || window;
  }
  return e;
}
function it(e) {
  var t = fe(e).Element;
  return e instanceof t || e instanceof Element;
}
function _e(e) {
  var t = fe(e).HTMLElement;
  return e instanceof t || e instanceof HTMLElement;
}
function rr(e) {
  if (typeof ShadowRoot > "u") return !1;
  var t = fe(e).ShadowRoot;
  return e instanceof t || e instanceof ShadowRoot;
}
function xa(e) {
  var t = e.state;
  Object.keys(t.elements).forEach(function (n) {
    var r = t.styles[n] || {},
      i = t.attributes[n] || {},
      o = t.elements[n];
    !_e(o) ||
      !Me(o) ||
      (Object.assign(o.style, r),
      Object.keys(i).forEach(function (a) {
        var s = i[a];
        s === !1 ? o.removeAttribute(a) : o.setAttribute(a, s === !0 ? "" : s);
      }));
  });
}
function Ea(e) {
  var t = e.state,
    n = {
      popper: {
        position: t.options.strategy,
        left: "0",
        top: "0",
        margin: "0",
      },
      arrow: { position: "absolute" },
      reference: {},
    };
  return (
    Object.assign(t.elements.popper.style, n.popper),
    (t.styles = n),
    t.elements.arrow && Object.assign(t.elements.arrow.style, n.arrow),
    function () {
      Object.keys(t.elements).forEach(function (r) {
        var i = t.elements[r],
          o = t.attributes[r] || {},
          a = Object.keys(t.styles.hasOwnProperty(r) ? t.styles[r] : n[r]),
          s = a.reduce(function (c, f) {
            return (c[f] = ""), c;
          }, {});
        !_e(i) ||
          !Me(i) ||
          (Object.assign(i.style, s),
          Object.keys(o).forEach(function (c) {
            i.removeAttribute(c);
          }));
      });
    }
  );
}
const Ei = {
  name: "applyStyles",
  enabled: !0,
  phase: "write",
  fn: xa,
  effect: Ea,
  requires: ["computeStyles"],
};
function Te(e) {
  return e.split("-")[0];
}
var Ze = Math.max,
  rn = Math.min,
  pt = Math.round;
function Pn() {
  var e = navigator.userAgentData;
  return e != null && e.brands && Array.isArray(e.brands)
    ? e.brands
        .map(function (t) {
          return t.brand + "/" + t.version;
        })
        .join(" ")
    : navigator.userAgent;
}
function wi() {
  return !/^((?!chrome|android).)*safari/i.test(Pn());
}
function ht(e, t, n) {
  t === void 0 && (t = !1), n === void 0 && (n = !1);
  var r = e.getBoundingClientRect(),
    i = 1,
    o = 1;
  t &&
    _e(e) &&
    ((i = (e.offsetWidth > 0 && pt(r.width) / e.offsetWidth) || 1),
    (o = (e.offsetHeight > 0 && pt(r.height) / e.offsetHeight) || 1));
  var a = it(e) ? fe(e) : window,
    s = a.visualViewport,
    c = !wi() && n,
    f = (r.left + (c && s ? s.offsetLeft : 0)) / i,
    l = (r.top + (c && s ? s.offsetTop : 0)) / o,
    v = r.width / i,
    b = r.height / o;
  return {
    width: v,
    height: b,
    top: l,
    right: f + v,
    bottom: l + b,
    left: f,
    x: f,
    y: l,
  };
}
function ir(e) {
  var t = ht(e),
    n = e.offsetWidth,
    r = e.offsetHeight;
  return (
    Math.abs(t.width - n) <= 1 && (n = t.width),
    Math.abs(t.height - r) <= 1 && (r = t.height),
    { x: e.offsetLeft, y: e.offsetTop, width: n, height: r }
  );
}
function Oi(e, t) {
  var n = t.getRootNode && t.getRootNode();
  if (e.contains(t)) return !0;
  if (n && rr(n)) {
    var r = t;
    do {
      if (r && e.isSameNode(r)) return !0;
      r = r.parentNode || r.host;
    } while (r);
  }
  return !1;
}
function De(e) {
  return fe(e).getComputedStyle(e);
}
function wa(e) {
  return ["table", "td", "th"].indexOf(Me(e)) >= 0;
}
function We(e) {
  return ((it(e) ? e.ownerDocument : e.document) || window.document)
    .documentElement;
}
function fn(e) {
  return Me(e) === "html"
    ? e
    : e.assignedSlot || e.parentNode || (rr(e) ? e.host : null) || We(e);
}
function kr(e) {
  return !_e(e) || De(e).position === "fixed" ? null : e.offsetParent;
}
function Oa(e) {
  var t = /firefox/i.test(Pn()),
    n = /Trident/i.test(Pn());
  if (n && _e(e)) {
    var r = De(e);
    if (r.position === "fixed") return null;
  }
  var i = fn(e);
  for (rr(i) && (i = i.host); _e(i) && ["html", "body"].indexOf(Me(i)) < 0; ) {
    var o = De(i);
    if (
      o.transform !== "none" ||
      o.perspective !== "none" ||
      o.contain === "paint" ||
      ["transform", "perspective"].indexOf(o.willChange) !== -1 ||
      (t && o.willChange === "filter") ||
      (t && o.filter && o.filter !== "none")
    )
      return i;
    i = i.parentNode;
  }
  return null;
}
function $t(e) {
  for (var t = fe(e), n = kr(e); n && wa(n) && De(n).position === "static"; )
    n = kr(n);
  return n &&
    (Me(n) === "html" || (Me(n) === "body" && De(n).position === "static"))
    ? t
    : n || Oa(e) || t;
}
function or(e) {
  return ["top", "bottom"].indexOf(e) >= 0 ? "x" : "y";
}
function Tt(e, t, n) {
  return Ze(e, rn(t, n));
}
function Aa(e, t, n) {
  var r = Tt(e, t, n);
  return r > n ? n : r;
}
function Ai() {
  return { top: 0, right: 0, bottom: 0, left: 0 };
}
function Si(e) {
  return Object.assign({}, Ai(), e);
}
function Ti(e, t) {
  return t.reduce(function (n, r) {
    return (n[r] = e), n;
  }, {});
}
var Sa = function (t, n) {
  return (
    (t =
      typeof t == "function"
        ? t(Object.assign({}, n.rects, { placement: n.placement }))
        : t),
    Si(typeof t != "number" ? t : Ti(t, Bt))
  );
};
function Ta(e) {
  var t,
    n = e.state,
    r = e.name,
    i = e.options,
    o = n.elements.arrow,
    a = n.modifiersData.popperOffsets,
    s = Te(n.placement),
    c = or(s),
    f = [ue, ye].indexOf(s) >= 0,
    l = f ? "height" : "width";
  if (!(!o || !a)) {
    var v = Sa(i.padding, n),
      b = ir(o),
      _ = c === "y" ? ce : ue,
      A = c === "y" ? be : ye,
      S =
        n.rects.reference[l] + n.rects.reference[c] - a[c] - n.rects.popper[l],
      h = a[c] - n.rects.reference[c],
      E = $t(o),
      O = E ? (c === "y" ? E.clientHeight || 0 : E.clientWidth || 0) : 0,
      M = S / 2 - h / 2,
      u = v[_],
      I = O - b[l] - v[A],
      m = O / 2 - b[l] / 2 + M,
      L = Tt(u, m, I),
      K = c;
    n.modifiersData[r] = ((t = {}), (t[K] = L), (t.centerOffset = L - m), t);
  }
}
function Ma(e) {
  var t = e.state,
    n = e.options,
    r = n.element,
    i = r === void 0 ? "[data-popper-arrow]" : r;
  i != null &&
    ((typeof i == "string" && ((i = t.elements.popper.querySelector(i)), !i)) ||
      (Oi(t.elements.popper, i) && (t.elements.arrow = i)));
}
const Ca = {
  name: "arrow",
  enabled: !0,
  phase: "main",
  fn: Ta,
  effect: Ma,
  requires: ["popperOffsets"],
  requiresIfExists: ["preventOverflow"],
};
function gt(e) {
  return e.split("-")[1];
}
var Ra = { top: "auto", right: "auto", bottom: "auto", left: "auto" };
function Ia(e, t) {
  var n = e.x,
    r = e.y,
    i = t.devicePixelRatio || 1;
  return { x: pt(n * i) / i || 0, y: pt(r * i) / i || 0 };
}
function Br(e) {
  var t,
    n = e.popper,
    r = e.popperRect,
    i = e.placement,
    o = e.variation,
    a = e.offsets,
    s = e.position,
    c = e.gpuAcceleration,
    f = e.adaptive,
    l = e.roundOffsets,
    v = e.isFixed,
    b = a.x,
    _ = b === void 0 ? 0 : b,
    A = a.y,
    S = A === void 0 ? 0 : A,
    h = typeof l == "function" ? l({ x: _, y: S }) : { x: _, y: S };
  (_ = h.x), (S = h.y);
  var E = a.hasOwnProperty("x"),
    O = a.hasOwnProperty("y"),
    M = ue,
    u = ce,
    I = window;
  if (f) {
    var m = $t(n),
      L = "clientHeight",
      K = "clientWidth";
    if (
      (m === fe(n) &&
        ((m = We(n)),
        De(m).position !== "static" &&
          s === "absolute" &&
          ((L = "scrollHeight"), (K = "scrollWidth"))),
      (m = m),
      i === ce || ((i === ue || i === ye) && o === Dt))
    ) {
      u = be;
      var P = v && m === I && I.visualViewport ? I.visualViewport.height : m[L];
      (S -= P - r.height), (S *= c ? 1 : -1);
    }
    if (i === ue || ((i === ce || i === be) && o === Dt)) {
      M = ye;
      var H = v && m === I && I.visualViewport ? I.visualViewport.width : m[K];
      (_ -= H - r.width), (_ *= c ? 1 : -1);
    }
  }
  var q = Object.assign({ position: s }, f && Ra),
    z = l === !0 ? Ia({ x: _, y: S }, fe(n)) : { x: _, y: S };
  if (((_ = z.x), (S = z.y), c)) {
    var k;
    return Object.assign(
      {},
      q,
      ((k = {}),
      (k[u] = O ? "0" : ""),
      (k[M] = E ? "0" : ""),
      (k.transform =
        (I.devicePixelRatio || 1) <= 1
          ? "translate(" + _ + "px, " + S + "px)"
          : "translate3d(" + _ + "px, " + S + "px, 0)"),
      k),
    );
  }
  return Object.assign(
    {},
    q,
    ((t = {}),
    (t[u] = O ? S + "px" : ""),
    (t[M] = E ? _ + "px" : ""),
    (t.transform = ""),
    t),
  );
}
function Da(e) {
  var t = e.state,
    n = e.options,
    r = n.gpuAcceleration,
    i = r === void 0 ? !0 : r,
    o = n.adaptive,
    a = o === void 0 ? !0 : o,
    s = n.roundOffsets,
    c = s === void 0 ? !0 : s,
    f = {
      placement: Te(t.placement),
      variation: gt(t.placement),
      popper: t.elements.popper,
      popperRect: t.rects.popper,
      gpuAcceleration: i,
      isFixed: t.options.strategy === "fixed",
    };
  t.modifiersData.popperOffsets != null &&
    (t.styles.popper = Object.assign(
      {},
      t.styles.popper,
      Br(
        Object.assign({}, f, {
          offsets: t.modifiersData.popperOffsets,
          position: t.options.strategy,
          adaptive: a,
          roundOffsets: c,
        }),
      ),
    )),
    t.modifiersData.arrow != null &&
      (t.styles.arrow = Object.assign(
        {},
        t.styles.arrow,
        Br(
          Object.assign({}, f, {
            offsets: t.modifiersData.arrow,
            position: "absolute",
            adaptive: !1,
            roundOffsets: c,
          }),
        ),
      )),
    (t.attributes.popper = Object.assign({}, t.attributes.popper, {
      "data-popper-placement": t.placement,
    }));
}
const Na = {
  name: "computeStyles",
  enabled: !0,
  phase: "beforeWrite",
  fn: Da,
  data: {},
};
var Vt = { passive: !0 };
function La(e) {
  var t = e.state,
    n = e.instance,
    r = e.options,
    i = r.scroll,
    o = i === void 0 ? !0 : i,
    a = r.resize,
    s = a === void 0 ? !0 : a,
    c = fe(t.elements.popper),
    f = [].concat(t.scrollParents.reference, t.scrollParents.popper);
  return (
    o &&
      f.forEach(function (l) {
        l.addEventListener("scroll", n.update, Vt);
      }),
    s && c.addEventListener("resize", n.update, Vt),
    function () {
      o &&
        f.forEach(function (l) {
          l.removeEventListener("scroll", n.update, Vt);
        }),
        s && c.removeEventListener("resize", n.update, Vt);
    }
  );
}
const Pa = {
  name: "eventListeners",
  enabled: !0,
  phase: "write",
  fn: function () {},
  effect: La,
  data: {},
};
var ka = { left: "right", right: "left", bottom: "top", top: "bottom" };
function en(e) {
  return e.replace(/left|right|bottom|top/g, function (t) {
    return ka[t];
  });
}
var Ba = { start: "end", end: "start" };
function $r(e) {
  return e.replace(/start|end/g, function (t) {
    return Ba[t];
  });
}
function ar(e) {
  var t = fe(e),
    n = t.pageXOffset,
    r = t.pageYOffset;
  return { scrollLeft: n, scrollTop: r };
}
function sr(e) {
  return ht(We(e)).left + ar(e).scrollLeft;
}
function $a(e, t) {
  var n = fe(e),
    r = We(e),
    i = n.visualViewport,
    o = r.clientWidth,
    a = r.clientHeight,
    s = 0,
    c = 0;
  if (i) {
    (o = i.width), (a = i.height);
    var f = wi();
    (f || (!f && t === "fixed")) && ((s = i.offsetLeft), (c = i.offsetTop));
  }
  return { width: o, height: a, x: s + sr(e), y: c };
}
function ja(e) {
  var t,
    n = We(e),
    r = ar(e),
    i = (t = e.ownerDocument) == null ? void 0 : t.body,
    o = Ze(
      n.scrollWidth,
      n.clientWidth,
      i ? i.scrollWidth : 0,
      i ? i.clientWidth : 0,
    ),
    a = Ze(
      n.scrollHeight,
      n.clientHeight,
      i ? i.scrollHeight : 0,
      i ? i.clientHeight : 0,
    ),
    s = -r.scrollLeft + sr(e),
    c = -r.scrollTop;
  return (
    De(i || n).direction === "rtl" &&
      (s += Ze(n.clientWidth, i ? i.clientWidth : 0) - o),
    { width: o, height: a, x: s, y: c }
  );
}
function cr(e) {
  var t = De(e),
    n = t.overflow,
    r = t.overflowX,
    i = t.overflowY;
  return /auto|scroll|overlay|hidden/.test(n + i + r);
}
function Mi(e) {
  return ["html", "body", "#document"].indexOf(Me(e)) >= 0
    ? e.ownerDocument.body
    : _e(e) && cr(e)
      ? e
      : Mi(fn(e));
}
function Mt(e, t) {
  var n;
  t === void 0 && (t = []);
  var r = Mi(e),
    i = r === ((n = e.ownerDocument) == null ? void 0 : n.body),
    o = fe(r),
    a = i ? [o].concat(o.visualViewport || [], cr(r) ? r : []) : r,
    s = t.concat(a);
  return i ? s : s.concat(Mt(fn(a)));
}
function kn(e) {
  return Object.assign({}, e, {
    left: e.x,
    top: e.y,
    right: e.x + e.width,
    bottom: e.y + e.height,
  });
}
function Ha(e, t) {
  var n = ht(e, !1, t === "fixed");
  return (
    (n.top = n.top + e.clientTop),
    (n.left = n.left + e.clientLeft),
    (n.bottom = n.top + e.clientHeight),
    (n.right = n.left + e.clientWidth),
    (n.width = e.clientWidth),
    (n.height = e.clientHeight),
    (n.x = n.left),
    (n.y = n.top),
    n
  );
}
function jr(e, t, n) {
  return t === mi ? kn($a(e, n)) : it(t) ? Ha(t, n) : kn(ja(We(e)));
}
function Fa(e) {
  var t = Mt(fn(e)),
    n = ["absolute", "fixed"].indexOf(De(e).position) >= 0,
    r = n && _e(e) ? $t(e) : e;
  return it(r)
    ? t.filter(function (i) {
        return it(i) && Oi(i, r) && Me(i) !== "body";
      })
    : [];
}
function Ua(e, t, n, r) {
  var i = t === "clippingParents" ? Fa(e) : [].concat(t),
    o = [].concat(i, [n]),
    a = o[0],
    s = o.reduce(
      function (c, f) {
        var l = jr(e, f, r);
        return (
          (c.top = Ze(l.top, c.top)),
          (c.right = rn(l.right, c.right)),
          (c.bottom = rn(l.bottom, c.bottom)),
          (c.left = Ze(l.left, c.left)),
          c
        );
      },
      jr(e, a, r),
    );
  return (
    (s.width = s.right - s.left),
    (s.height = s.bottom - s.top),
    (s.x = s.left),
    (s.y = s.top),
    s
  );
}
function Ci(e) {
  var t = e.reference,
    n = e.element,
    r = e.placement,
    i = r ? Te(r) : null,
    o = r ? gt(r) : null,
    a = t.x + t.width / 2 - n.width / 2,
    s = t.y + t.height / 2 - n.height / 2,
    c;
  switch (i) {
    case ce:
      c = { x: a, y: t.y - n.height };
      break;
    case be:
      c = { x: a, y: t.y + t.height };
      break;
    case ye:
      c = { x: t.x + t.width, y: s };
      break;
    case ue:
      c = { x: t.x - n.width, y: s };
      break;
    default:
      c = { x: t.x, y: t.y };
  }
  var f = i ? or(i) : null;
  if (f != null) {
    var l = f === "y" ? "height" : "width";
    switch (o) {
      case dt:
        c[f] = c[f] - (t[l] / 2 - n[l] / 2);
        break;
      case Dt:
        c[f] = c[f] + (t[l] / 2 - n[l] / 2);
        break;
    }
  }
  return c;
}
function Nt(e, t) {
  t === void 0 && (t = {});
  var n = t,
    r = n.placement,
    i = r === void 0 ? e.placement : r,
    o = n.strategy,
    a = o === void 0 ? e.strategy : o,
    s = n.boundary,
    c = s === void 0 ? ua : s,
    f = n.rootBoundary,
    l = f === void 0 ? mi : f,
    v = n.elementContext,
    b = v === void 0 ? Et : v,
    _ = n.altBoundary,
    A = _ === void 0 ? !1 : _,
    S = n.padding,
    h = S === void 0 ? 0 : S,
    E = Si(typeof h != "number" ? h : Ti(h, Bt)),
    O = b === Et ? la : Et,
    M = e.rects.popper,
    u = e.elements[A ? O : b],
    I = Ua(it(u) ? u : u.contextElement || We(e.elements.popper), c, l, a),
    m = ht(e.elements.reference),
    L = Ci({ reference: m, element: M, strategy: "absolute", placement: i }),
    K = kn(Object.assign({}, M, L)),
    P = b === Et ? K : m,
    H = {
      top: I.top - P.top + E.top,
      bottom: P.bottom - I.bottom + E.bottom,
      left: I.left - P.left + E.left,
      right: P.right - I.right + E.right,
    },
    q = e.modifiersData.offset;
  if (b === Et && q) {
    var z = q[i];
    Object.keys(H).forEach(function (k) {
      var U = [ye, be].indexOf(k) >= 0 ? 1 : -1,
        Y = [ce, be].indexOf(k) >= 0 ? "y" : "x";
      H[k] += z[Y] * U;
    });
  }
  return H;
}
function Wa(e, t) {
  t === void 0 && (t = {});
  var n = t,
    r = n.placement,
    i = n.boundary,
    o = n.rootBoundary,
    a = n.padding,
    s = n.flipVariations,
    c = n.allowedAutoPlacements,
    f = c === void 0 ? xi : c,
    l = gt(r),
    v = l
      ? s
        ? Pr
        : Pr.filter(function (A) {
            return gt(A) === l;
          })
      : Bt,
    b = v.filter(function (A) {
      return f.indexOf(A) >= 0;
    });
  b.length === 0 && (b = v);
  var _ = b.reduce(function (A, S) {
    return (
      (A[S] = Nt(e, { placement: S, boundary: i, rootBoundary: o, padding: a })[
        Te(S)
      ]),
      A
    );
  }, {});
  return Object.keys(_).sort(function (A, S) {
    return _[A] - _[S];
  });
}
function Ka(e) {
  if (Te(e) === nr) return [];
  var t = en(e);
  return [$r(e), t, $r(t)];
}
function za(e) {
  var t = e.state,
    n = e.options,
    r = e.name;
  if (!t.modifiersData[r]._skip) {
    for (
      var i = n.mainAxis,
        o = i === void 0 ? !0 : i,
        a = n.altAxis,
        s = a === void 0 ? !0 : a,
        c = n.fallbackPlacements,
        f = n.padding,
        l = n.boundary,
        v = n.rootBoundary,
        b = n.altBoundary,
        _ = n.flipVariations,
        A = _ === void 0 ? !0 : _,
        S = n.allowedAutoPlacements,
        h = t.options.placement,
        E = Te(h),
        O = E === h,
        M = c || (O || !A ? [en(h)] : Ka(h)),
        u = [h].concat(M).reduce(function (se, G) {
          return se.concat(
            Te(G) === nr
              ? Wa(t, {
                  placement: G,
                  boundary: l,
                  rootBoundary: v,
                  padding: f,
                  flipVariations: A,
                  allowedAutoPlacements: S,
                })
              : G,
          );
        }, []),
        I = t.rects.reference,
        m = t.rects.popper,
        L = new Map(),
        K = !0,
        P = u[0],
        H = 0;
      H < u.length;
      H++
    ) {
      var q = u[H],
        z = Te(q),
        k = gt(q) === dt,
        U = [ce, be].indexOf(z) >= 0,
        Y = U ? "width" : "height",
        te = Nt(t, {
          placement: q,
          boundary: l,
          rootBoundary: v,
          altBoundary: b,
          padding: f,
        }),
        p = U ? (k ? ye : ue) : k ? be : ce;
      I[Y] > m[Y] && (p = en(p));
      var y = en(p),
        C = [];
      if (
        (o && C.push(te[z] <= 0),
        s && C.push(te[p] <= 0, te[y] <= 0),
        C.every(function (se) {
          return se;
        }))
      ) {
        (P = q), (K = !1);
        break;
      }
      L.set(q, C);
    }
    if (K)
      for (
        var N = A ? 3 : 1,
          W = function (G) {
            var Z = u.find(function (Ce) {
              var de = L.get(Ce);
              if (de)
                return de.slice(0, G).every(function (Re) {
                  return Re;
                });
            });
            if (Z) return (P = Z), "break";
          },
          J = N;
        J > 0;
        J--
      ) {
        var re = W(J);
        if (re === "break") break;
      }
    t.placement !== P &&
      ((t.modifiersData[r]._skip = !0), (t.placement = P), (t.reset = !0));
  }
}
const Va = {
  name: "flip",
  enabled: !0,
  phase: "main",
  fn: za,
  requiresIfExists: ["offset"],
  data: { _skip: !1 },
};
function Hr(e, t, n) {
  return (
    n === void 0 && (n = { x: 0, y: 0 }),
    {
      top: e.top - t.height - n.y,
      right: e.right - t.width + n.x,
      bottom: e.bottom - t.height + n.y,
      left: e.left - t.width - n.x,
    }
  );
}
function Fr(e) {
  return [ce, ye, be, ue].some(function (t) {
    return e[t] >= 0;
  });
}
function qa(e) {
  var t = e.state,
    n = e.name,
    r = t.rects.reference,
    i = t.rects.popper,
    o = t.modifiersData.preventOverflow,
    a = Nt(t, { elementContext: "reference" }),
    s = Nt(t, { altBoundary: !0 }),
    c = Hr(a, r),
    f = Hr(s, i, o),
    l = Fr(c),
    v = Fr(f);
  (t.modifiersData[n] = {
    referenceClippingOffsets: c,
    popperEscapeOffsets: f,
    isReferenceHidden: l,
    hasPopperEscaped: v,
  }),
    (t.attributes.popper = Object.assign({}, t.attributes.popper, {
      "data-popper-reference-hidden": l,
      "data-popper-escaped": v,
    }));
}
const Ga = {
  name: "hide",
  enabled: !0,
  phase: "main",
  requiresIfExists: ["preventOverflow"],
  fn: qa,
};
function Xa(e, t, n) {
  var r = Te(e),
    i = [ue, ce].indexOf(r) >= 0 ? -1 : 1,
    o = typeof n == "function" ? n(Object.assign({}, t, { placement: e })) : n,
    a = o[0],
    s = o[1];
  return (
    (a = a || 0),
    (s = (s || 0) * i),
    [ue, ye].indexOf(r) >= 0 ? { x: s, y: a } : { x: a, y: s }
  );
}
function Ya(e) {
  var t = e.state,
    n = e.options,
    r = e.name,
    i = n.offset,
    o = i === void 0 ? [0, 0] : i,
    a = xi.reduce(function (l, v) {
      return (l[v] = Xa(v, t.rects, o)), l;
    }, {}),
    s = a[t.placement],
    c = s.x,
    f = s.y;
  t.modifiersData.popperOffsets != null &&
    ((t.modifiersData.popperOffsets.x += c),
    (t.modifiersData.popperOffsets.y += f)),
    (t.modifiersData[r] = a);
}
const Ja = {
  name: "offset",
  enabled: !0,
  phase: "main",
  requires: ["popperOffsets"],
  fn: Ya,
};
function Za(e) {
  var t = e.state,
    n = e.name;
  t.modifiersData[n] = Ci({
    reference: t.rects.reference,
    element: t.rects.popper,
    strategy: "absolute",
    placement: t.placement,
  });
}
const Qa = {
  name: "popperOffsets",
  enabled: !0,
  phase: "read",
  fn: Za,
  data: {},
};
function es(e) {
  return e === "x" ? "y" : "x";
}
function ts(e) {
  var t = e.state,
    n = e.options,
    r = e.name,
    i = n.mainAxis,
    o = i === void 0 ? !0 : i,
    a = n.altAxis,
    s = a === void 0 ? !1 : a,
    c = n.boundary,
    f = n.rootBoundary,
    l = n.altBoundary,
    v = n.padding,
    b = n.tether,
    _ = b === void 0 ? !0 : b,
    A = n.tetherOffset,
    S = A === void 0 ? 0 : A,
    h = Nt(t, { boundary: c, rootBoundary: f, padding: v, altBoundary: l }),
    E = Te(t.placement),
    O = gt(t.placement),
    M = !O,
    u = or(E),
    I = es(u),
    m = t.modifiersData.popperOffsets,
    L = t.rects.reference,
    K = t.rects.popper,
    P =
      typeof S == "function"
        ? S(Object.assign({}, t.rects, { placement: t.placement }))
        : S,
    H =
      typeof P == "number"
        ? { mainAxis: P, altAxis: P }
        : Object.assign({ mainAxis: 0, altAxis: 0 }, P),
    q = t.modifiersData.offset ? t.modifiersData.offset[t.placement] : null,
    z = { x: 0, y: 0 };
  if (m) {
    if (o) {
      var k,
        U = u === "y" ? ce : ue,
        Y = u === "y" ? be : ye,
        te = u === "y" ? "height" : "width",
        p = m[u],
        y = p + h[U],
        C = p - h[Y],
        N = _ ? -K[te] / 2 : 0,
        W = O === dt ? L[te] : K[te],
        J = O === dt ? -K[te] : -L[te],
        re = t.elements.arrow,
        se = _ && re ? ir(re) : { width: 0, height: 0 },
        G = t.modifiersData["arrow#persistent"]
          ? t.modifiersData["arrow#persistent"].padding
          : Ai(),
        Z = G[U],
        Ce = G[Y],
        de = Tt(0, L[te], se[te]),
        Re = M ? L[te] / 2 - N - de - Z - H.mainAxis : W - de - Z - H.mainAxis,
        Oe = M
          ? -L[te] / 2 + N + de + Ce + H.mainAxis
          : J + de + Ce + H.mainAxis,
        Le = t.elements.arrow && $t(t.elements.arrow),
        st = Le ? (u === "y" ? Le.clientTop || 0 : Le.clientLeft || 0) : 0,
        ze = (k = q == null ? void 0 : q[u]) != null ? k : 0,
        Ie = p + Re - ze - st,
        Ve = p + Oe - ze,
        ie = Tt(_ ? rn(y, Ie) : y, p, _ ? Ze(C, Ve) : C);
      (m[u] = ie), (z[u] = ie - p);
    }
    if (s) {
      var qe,
        Pe = u === "x" ? ce : ue,
        T = u === "x" ? be : ye,
        pe = m[I],
        V = I === "y" ? "height" : "width",
        $ = pe + h[Pe],
        he = pe - h[T],
        le = [ce, ue].indexOf(E) !== -1,
        ke = (qe = q == null ? void 0 : q[I]) != null ? qe : 0,
        Be = le ? $ : pe - L[V] - K[V] - ke + H.altAxis,
        g = le ? pe + L[V] + K[V] - ke - H.altAxis : he,
        x = _ && le ? Aa(Be, pe, g) : Tt(_ ? Be : $, pe, _ ? g : he);
      (m[I] = x), (z[I] = x - pe);
    }
    t.modifiersData[r] = z;
  }
}
const ns = {
  name: "preventOverflow",
  enabled: !0,
  phase: "main",
  fn: ts,
  requiresIfExists: ["offset"],
};
function rs(e) {
  return { scrollLeft: e.scrollLeft, scrollTop: e.scrollTop };
}
function is(e) {
  return e === fe(e) || !_e(e) ? ar(e) : rs(e);
}
function os(e) {
  var t = e.getBoundingClientRect(),
    n = pt(t.width) / e.offsetWidth || 1,
    r = pt(t.height) / e.offsetHeight || 1;
  return n !== 1 || r !== 1;
}
function as(e, t, n) {
  n === void 0 && (n = !1);
  var r = _e(t),
    i = _e(t) && os(t),
    o = We(t),
    a = ht(e, i, n),
    s = { scrollLeft: 0, scrollTop: 0 },
    c = { x: 0, y: 0 };
  return (
    (r || (!r && !n)) &&
      ((Me(t) !== "body" || cr(o)) && (s = is(t)),
      _e(t)
        ? ((c = ht(t, !0)), (c.x += t.clientLeft), (c.y += t.clientTop))
        : o && (c.x = sr(o))),
    {
      x: a.left + s.scrollLeft - c.x,
      y: a.top + s.scrollTop - c.y,
      width: a.width,
      height: a.height,
    }
  );
}
function ss(e) {
  var t = new Map(),
    n = new Set(),
    r = [];
  e.forEach(function (o) {
    t.set(o.name, o);
  });
  function i(o) {
    n.add(o.name);
    var a = [].concat(o.requires || [], o.requiresIfExists || []);
    a.forEach(function (s) {
      if (!n.has(s)) {
        var c = t.get(s);
        c && i(c);
      }
    }),
      r.push(o);
  }
  return (
    e.forEach(function (o) {
      n.has(o.name) || i(o);
    }),
    r
  );
}
function cs(e) {
  var t = ss(e);
  return ma.reduce(function (n, r) {
    return n.concat(
      t.filter(function (i) {
        return i.phase === r;
      }),
    );
  }, []);
}
function us(e) {
  var t;
  return function () {
    return (
      t ||
        (t = new Promise(function (n) {
          Promise.resolve().then(function () {
            (t = void 0), n(e());
          });
        })),
      t
    );
  };
}
function ls(e) {
  var t = e.reduce(function (n, r) {
    var i = n[r.name];
    return (
      (n[r.name] = i
        ? Object.assign({}, i, r, {
            options: Object.assign({}, i.options, r.options),
            data: Object.assign({}, i.data, r.data),
          })
        : r),
      n
    );
  }, {});
  return Object.keys(t).map(function (n) {
    return t[n];
  });
}
var Ur = { placement: "bottom", modifiers: [], strategy: "absolute" };
function Wr() {
  for (var e = arguments.length, t = new Array(e), n = 0; n < e; n++)
    t[n] = arguments[n];
  return !t.some(function (r) {
    return !(r && typeof r.getBoundingClientRect == "function");
  });
}
function fs(e) {
  e === void 0 && (e = {});
  var t = e,
    n = t.defaultModifiers,
    r = n === void 0 ? [] : n,
    i = t.defaultOptions,
    o = i === void 0 ? Ur : i;
  return function (s, c, f) {
    f === void 0 && (f = o);
    var l = {
        placement: "bottom",
        orderedModifiers: [],
        options: Object.assign({}, Ur, o),
        modifiersData: {},
        elements: { reference: s, popper: c },
        attributes: {},
        styles: {},
      },
      v = [],
      b = !1,
      _ = {
        state: l,
        setOptions: function (E) {
          var O = typeof E == "function" ? E(l.options) : E;
          S(),
            (l.options = Object.assign({}, o, l.options, O)),
            (l.scrollParents = {
              reference: it(s)
                ? Mt(s)
                : s.contextElement
                  ? Mt(s.contextElement)
                  : [],
              popper: Mt(c),
            });
          var M = cs(ls([].concat(r, l.options.modifiers)));
          return (
            (l.orderedModifiers = M.filter(function (u) {
              return u.enabled;
            })),
            A(),
            _.update()
          );
        },
        forceUpdate: function () {
          if (!b) {
            var E = l.elements,
              O = E.reference,
              M = E.popper;
            if (Wr(O, M)) {
              (l.rects = {
                reference: as(O, $t(M), l.options.strategy === "fixed"),
                popper: ir(M),
              }),
                (l.reset = !1),
                (l.placement = l.options.placement),
                l.orderedModifiers.forEach(function (H) {
                  return (l.modifiersData[H.name] = Object.assign({}, H.data));
                });
              for (var u = 0; u < l.orderedModifiers.length; u++) {
                if (l.reset === !0) {
                  (l.reset = !1), (u = -1);
                  continue;
                }
                var I = l.orderedModifiers[u],
                  m = I.fn,
                  L = I.options,
                  K = L === void 0 ? {} : L,
                  P = I.name;
                typeof m == "function" &&
                  (l = m({ state: l, options: K, name: P, instance: _ }) || l);
              }
            }
          }
        },
        update: us(function () {
          return new Promise(function (h) {
            _.forceUpdate(), h(l);
          });
        }),
        destroy: function () {
          S(), (b = !0);
        },
      };
    if (!Wr(s, c)) return _;
    _.setOptions(f).then(function (h) {
      !b && f.onFirstUpdate && f.onFirstUpdate(h);
    });
    function A() {
      l.orderedModifiers.forEach(function (h) {
        var E = h.name,
          O = h.options,
          M = O === void 0 ? {} : O,
          u = h.effect;
        if (typeof u == "function") {
          var I = u({ state: l, name: E, instance: _, options: M }),
            m = function () {};
          v.push(I || m);
        }
      });
    }
    function S() {
      v.forEach(function (h) {
        return h();
      }),
        (v = []);
    }
    return _;
  };
}
var ds = [Pa, Qa, Na, Ei, Ja, Va, ns, Ca, Ga],
  ps = fs({ defaultModifiers: ds }),
  hs = "tippy-box",
  Ri = "tippy-content",
  gs = "tippy-backdrop",
  Ii = "tippy-arrow",
  Di = "tippy-svg-arrow",
  Xe = { passive: !0, capture: !0 },
  Ni = function () {
    return document.body;
  };
function An(e, t, n) {
  if (Array.isArray(e)) {
    var r = e[t];
    return r ?? (Array.isArray(n) ? n[t] : n);
  }
  return e;
}
function ur(e, t) {
  var n = {}.toString.call(e);
  return n.indexOf("[object") === 0 && n.indexOf(t + "]") > -1;
}
function Li(e, t) {
  return typeof e == "function" ? e.apply(void 0, t) : e;
}
function Kr(e, t) {
  if (t === 0) return e;
  var n;
  return function (r) {
    clearTimeout(n),
      (n = setTimeout(function () {
        e(r);
      }, t));
  };
}
function vs(e) {
  return e.split(/\s+/).filter(Boolean);
}
function ft(e) {
  return [].concat(e);
}
function zr(e, t) {
  e.indexOf(t) === -1 && e.push(t);
}
function _s(e) {
  return e.filter(function (t, n) {
    return e.indexOf(t) === n;
  });
}
function bs(e) {
  return e.split("-")[0];
}
function on(e) {
  return [].slice.call(e);
}
function Vr(e) {
  return Object.keys(e).reduce(function (t, n) {
    return e[n] !== void 0 && (t[n] = e[n]), t;
  }, {});
}
function Ct() {
  return document.createElement("div");
}
function dn(e) {
  return ["Element", "Fragment"].some(function (t) {
    return ur(e, t);
  });
}
function ys(e) {
  return ur(e, "NodeList");
}
function ms(e) {
  return ur(e, "MouseEvent");
}
function xs(e) {
  return !!(e && e._tippy && e._tippy.reference === e);
}
function Es(e) {
  return dn(e)
    ? [e]
    : ys(e)
      ? on(e)
      : Array.isArray(e)
        ? e
        : on(document.querySelectorAll(e));
}
function Sn(e, t) {
  e.forEach(function (n) {
    n && (n.style.transitionDuration = t + "ms");
  });
}
function qr(e, t) {
  e.forEach(function (n) {
    n && n.setAttribute("data-state", t);
  });
}
function ws(e) {
  var t,
    n = ft(e),
    r = n[0];
  return r != null && (t = r.ownerDocument) != null && t.body
    ? r.ownerDocument
    : document;
}
function Os(e, t) {
  var n = t.clientX,
    r = t.clientY;
  return e.every(function (i) {
    var o = i.popperRect,
      a = i.popperState,
      s = i.props,
      c = s.interactiveBorder,
      f = bs(a.placement),
      l = a.modifiersData.offset;
    if (!l) return !0;
    var v = f === "bottom" ? l.top.y : 0,
      b = f === "top" ? l.bottom.y : 0,
      _ = f === "right" ? l.left.x : 0,
      A = f === "left" ? l.right.x : 0,
      S = o.top - r + v > c,
      h = r - o.bottom - b > c,
      E = o.left - n + _ > c,
      O = n - o.right - A > c;
    return S || h || E || O;
  });
}
function Tn(e, t, n) {
  var r = t + "EventListener";
  ["transitionend", "webkitTransitionEnd"].forEach(function (i) {
    e[r](i, n);
  });
}
function Gr(e, t) {
  for (var n = t; n; ) {
    var r;
    if (e.contains(n)) return !0;
    n =
      n.getRootNode == null || (r = n.getRootNode()) == null ? void 0 : r.host;
  }
  return !1;
}
var Se = { isTouch: !1 },
  Xr = 0;
function As() {
  Se.isTouch ||
    ((Se.isTouch = !0),
    window.performance && document.addEventListener("mousemove", Pi));
}
function Pi() {
  var e = performance.now();
  e - Xr < 20 &&
    ((Se.isTouch = !1), document.removeEventListener("mousemove", Pi)),
    (Xr = e);
}
function Ss() {
  var e = document.activeElement;
  if (xs(e)) {
    var t = e._tippy;
    e.blur && !t.state.isVisible && e.blur();
  }
}
function Ts() {
  document.addEventListener("touchstart", As, Xe),
    window.addEventListener("blur", Ss);
}
var Ms = typeof window < "u" && typeof document < "u",
  Cs = Ms ? !!window.msCrypto : !1,
  Rs = { animateFill: !1, followCursor: !1, inlinePositioning: !1, sticky: !1 },
  Is = {
    allowHTML: !1,
    animation: "fade",
    arrow: !0,
    content: "",
    inertia: !1,
    maxWidth: 350,
    role: "tooltip",
    theme: "",
    zIndex: 9999,
  },
  xe = Object.assign(
    {
      appendTo: Ni,
      aria: { content: "auto", expanded: "auto" },
      delay: 0,
      duration: [300, 250],
      getReferenceClientRect: null,
      hideOnClick: !0,
      ignoreAttributes: !1,
      interactive: !1,
      interactiveBorder: 2,
      interactiveDebounce: 0,
      moveTransition: "",
      offset: [0, 10],
      onAfterUpdate: function () {},
      onBeforeUpdate: function () {},
      onCreate: function () {},
      onDestroy: function () {},
      onHidden: function () {},
      onHide: function () {},
      onMount: function () {},
      onShow: function () {},
      onShown: function () {},
      onTrigger: function () {},
      onUntrigger: function () {},
      onClickOutside: function () {},
      placement: "top",
      plugins: [],
      popperOptions: {},
      render: null,
      showOnCreate: !1,
      touch: !0,
      trigger: "mouseenter focus",
      triggerTarget: null,
    },
    Rs,
    Is,
  ),
  Ds = Object.keys(xe),
  Ns = function (t) {
    var n = Object.keys(t);
    n.forEach(function (r) {
      xe[r] = t[r];
    });
  };
function ki(e) {
  var t = e.plugins || [],
    n = t.reduce(function (r, i) {
      var o = i.name,
        a = i.defaultValue;
      if (o) {
        var s;
        r[o] = e[o] !== void 0 ? e[o] : (s = xe[o]) != null ? s : a;
      }
      return r;
    }, {});
  return Object.assign({}, e, n);
}
function Ls(e, t) {
  var n = t ? Object.keys(ki(Object.assign({}, xe, { plugins: t }))) : Ds,
    r = n.reduce(function (i, o) {
      var a = (e.getAttribute("data-tippy-" + o) || "").trim();
      if (!a) return i;
      if (o === "content") i[o] = a;
      else
        try {
          i[o] = JSON.parse(a);
        } catch {
          i[o] = a;
        }
      return i;
    }, {});
  return r;
}
function Yr(e, t) {
  var n = Object.assign(
    {},
    t,
    { content: Li(t.content, [e]) },
    t.ignoreAttributes ? {} : Ls(e, t.plugins),
  );
  return (
    (n.aria = Object.assign({}, xe.aria, n.aria)),
    (n.aria = {
      expanded: n.aria.expanded === "auto" ? t.interactive : n.aria.expanded,
      content:
        n.aria.content === "auto"
          ? t.interactive
            ? null
            : "describedby"
          : n.aria.content,
    }),
    n
  );
}
var Ps = function () {
  return "innerHTML";
};
function Bn(e, t) {
  e[Ps()] = t;
}
function Jr(e) {
  var t = Ct();
  return (
    e === !0
      ? (t.className = Ii)
      : ((t.className = Di), dn(e) ? t.appendChild(e) : Bn(t, e)),
    t
  );
}
function Zr(e, t) {
  dn(t.content)
    ? (Bn(e, ""), e.appendChild(t.content))
    : typeof t.content != "function" &&
      (t.allowHTML ? Bn(e, t.content) : (e.textContent = t.content));
}
function $n(e) {
  var t = e.firstElementChild,
    n = on(t.children);
  return {
    box: t,
    content: n.find(function (r) {
      return r.classList.contains(Ri);
    }),
    arrow: n.find(function (r) {
      return r.classList.contains(Ii) || r.classList.contains(Di);
    }),
    backdrop: n.find(function (r) {
      return r.classList.contains(gs);
    }),
  };
}
function Bi(e) {
  var t = Ct(),
    n = Ct();
  (n.className = hs),
    n.setAttribute("data-state", "hidden"),
    n.setAttribute("tabindex", "-1");
  var r = Ct();
  (r.className = Ri),
    r.setAttribute("data-state", "hidden"),
    Zr(r, e.props),
    t.appendChild(n),
    n.appendChild(r),
    i(e.props, e.props);
  function i(o, a) {
    var s = $n(t),
      c = s.box,
      f = s.content,
      l = s.arrow;
    a.theme
      ? c.setAttribute("data-theme", a.theme)
      : c.removeAttribute("data-theme"),
      typeof a.animation == "string"
        ? c.setAttribute("data-animation", a.animation)
        : c.removeAttribute("data-animation"),
      a.inertia
        ? c.setAttribute("data-inertia", "")
        : c.removeAttribute("data-inertia"),
      (c.style.maxWidth =
        typeof a.maxWidth == "number" ? a.maxWidth + "px" : a.maxWidth),
      a.role ? c.setAttribute("role", a.role) : c.removeAttribute("role"),
      (o.content !== a.content || o.allowHTML !== a.allowHTML) &&
        Zr(f, e.props),
      a.arrow
        ? l
          ? o.arrow !== a.arrow &&
            (c.removeChild(l), c.appendChild(Jr(a.arrow)))
          : c.appendChild(Jr(a.arrow))
        : l && c.removeChild(l);
  }
  return { popper: t, onUpdate: i };
}
Bi.$$tippy = !0;
var ks = 1,
  qt = [],
  Mn = [];
function Bs(e, t) {
  var n = Yr(e, Object.assign({}, xe, ki(Vr(t)))),
    r,
    i,
    o,
    a = !1,
    s = !1,
    c = !1,
    f = !1,
    l,
    v,
    b,
    _ = [],
    A = Kr(Ie, n.interactiveDebounce),
    S,
    h = ks++,
    E = null,
    O = _s(n.plugins),
    M = {
      isEnabled: !0,
      isVisible: !1,
      isDestroyed: !1,
      isMounted: !1,
      isShown: !1,
    },
    u = {
      id: h,
      reference: e,
      popper: Ct(),
      popperInstance: E,
      props: n,
      state: M,
      plugins: O,
      clearDelayTimeouts: Be,
      setProps: g,
      setContent: x,
      show: D,
      hide: j,
      hideWithInteractivity: ne,
      enable: le,
      disable: ke,
      unmount: me,
      destroy: En,
    };
  if (!n.render) return u;
  var I = n.render(u),
    m = I.popper,
    L = I.onUpdate;
  m.setAttribute("data-tippy-root", ""),
    (m.id = "tippy-" + u.id),
    (u.popper = m),
    (e._tippy = u),
    (m._tippy = u);
  var K = O.map(function (d) {
      return d.fn(u);
    }),
    P = e.hasAttribute("aria-expanded");
  return (
    Le(),
    N(),
    p(),
    y("onCreate", [u]),
    n.showOnCreate && $(),
    m.addEventListener("mouseenter", function () {
      u.props.interactive && u.state.isVisible && u.clearDelayTimeouts();
    }),
    m.addEventListener("mouseleave", function () {
      u.props.interactive &&
        u.props.trigger.indexOf("mouseenter") >= 0 &&
        U().addEventListener("mousemove", A);
    }),
    u
  );
  function H() {
    var d = u.props.touch;
    return Array.isArray(d) ? d : [d, 0];
  }
  function q() {
    return H()[0] === "hold";
  }
  function z() {
    var d;
    return !!((d = u.props.render) != null && d.$$tippy);
  }
  function k() {
    return S || e;
  }
  function U() {
    var d = k().parentNode;
    return d ? ws(d) : document;
  }
  function Y() {
    return $n(m);
  }
  function te(d) {
    return (u.state.isMounted && !u.state.isVisible) ||
      Se.isTouch ||
      (l && l.type === "focus")
      ? 0
      : An(u.props.delay, d ? 0 : 1, xe.delay);
  }
  function p(d) {
    d === void 0 && (d = !1),
      (m.style.pointerEvents = u.props.interactive && !d ? "" : "none"),
      (m.style.zIndex = "" + u.props.zIndex);
  }
  function y(d, w, R) {
    if (
      (R === void 0 && (R = !0),
      K.forEach(function (B) {
        B[d] && B[d].apply(B, w);
      }),
      R)
    ) {
      var F;
      (F = u.props)[d].apply(F, w);
    }
  }
  function C() {
    var d = u.props.aria;
    if (d.content) {
      var w = "aria-" + d.content,
        R = m.id,
        F = ft(u.props.triggerTarget || e);
      F.forEach(function (B) {
        var oe = B.getAttribute(w);
        if (u.state.isVisible) B.setAttribute(w, oe ? oe + " " + R : R);
        else {
          var ge = oe && oe.replace(R, "").trim();
          ge ? B.setAttribute(w, ge) : B.removeAttribute(w);
        }
      });
    }
  }
  function N() {
    if (!(P || !u.props.aria.expanded)) {
      var d = ft(u.props.triggerTarget || e);
      d.forEach(function (w) {
        u.props.interactive
          ? w.setAttribute(
              "aria-expanded",
              u.state.isVisible && w === k() ? "true" : "false",
            )
          : w.removeAttribute("aria-expanded");
      });
    }
  }
  function W() {
    U().removeEventListener("mousemove", A),
      (qt = qt.filter(function (d) {
        return d !== A;
      }));
  }
  function J(d) {
    if (!(Se.isTouch && (c || d.type === "mousedown"))) {
      var w = (d.composedPath && d.composedPath()[0]) || d.target;
      if (!(u.props.interactive && Gr(m, w))) {
        if (
          ft(u.props.triggerTarget || e).some(function (R) {
            return Gr(R, w);
          })
        ) {
          if (
            Se.isTouch ||
            (u.state.isVisible && u.props.trigger.indexOf("click") >= 0)
          )
            return;
        } else y("onClickOutside", [u, d]);
        u.props.hideOnClick === !0 &&
          (u.clearDelayTimeouts(),
          u.hide(),
          (s = !0),
          setTimeout(function () {
            s = !1;
          }),
          u.state.isMounted || Z());
      }
    }
  }
  function re() {
    c = !0;
  }
  function se() {
    c = !1;
  }
  function G() {
    var d = U();
    d.addEventListener("mousedown", J, !0),
      d.addEventListener("touchend", J, Xe),
      d.addEventListener("touchstart", se, Xe),
      d.addEventListener("touchmove", re, Xe);
  }
  function Z() {
    var d = U();
    d.removeEventListener("mousedown", J, !0),
      d.removeEventListener("touchend", J, Xe),
      d.removeEventListener("touchstart", se, Xe),
      d.removeEventListener("touchmove", re, Xe);
  }
  function Ce(d, w) {
    Re(d, function () {
      !u.state.isVisible && m.parentNode && m.parentNode.contains(m) && w();
    });
  }
  function de(d, w) {
    Re(d, w);
  }
  function Re(d, w) {
    var R = Y().box;
    function F(B) {
      B.target === R && (Tn(R, "remove", F), w());
    }
    if (d === 0) return w();
    Tn(R, "remove", v), Tn(R, "add", F), (v = F);
  }
  function Oe(d, w, R) {
    R === void 0 && (R = !1);
    var F = ft(u.props.triggerTarget || e);
    F.forEach(function (B) {
      B.addEventListener(d, w, R),
        _.push({ node: B, eventType: d, handler: w, options: R });
    });
  }
  function Le() {
    q() &&
      (Oe("touchstart", ze, { passive: !0 }),
      Oe("touchend", Ve, { passive: !0 })),
      vs(u.props.trigger).forEach(function (d) {
        if (d !== "manual")
          switch ((Oe(d, ze), d)) {
            case "mouseenter":
              Oe("mouseleave", Ve);
              break;
            case "focus":
              Oe(Cs ? "focusout" : "blur", ie);
              break;
            case "focusin":
              Oe("focusout", ie);
              break;
          }
      });
  }
  function st() {
    _.forEach(function (d) {
      var w = d.node,
        R = d.eventType,
        F = d.handler,
        B = d.options;
      w.removeEventListener(R, F, B);
    }),
      (_ = []);
  }
  function ze(d) {
    var w,
      R = !1;
    if (!(!u.state.isEnabled || qe(d) || s)) {
      var F = ((w = l) == null ? void 0 : w.type) === "focus";
      (l = d),
        (S = d.currentTarget),
        N(),
        !u.state.isVisible &&
          ms(d) &&
          qt.forEach(function (B) {
            return B(d);
          }),
        d.type === "click" &&
        (u.props.trigger.indexOf("mouseenter") < 0 || a) &&
        u.props.hideOnClick !== !1 &&
        u.state.isVisible
          ? (R = !0)
          : $(d),
        d.type === "click" && (a = !R),
        R && !F && he(d);
    }
  }
  function Ie(d) {
    var w = d.target,
      R = k().contains(w) || m.contains(w);
    if (!(d.type === "mousemove" && R)) {
      var F = V()
        .concat(m)
        .map(function (B) {
          var oe,
            ge = B._tippy,
            ct = (oe = ge.popperInstance) == null ? void 0 : oe.state;
          return ct
            ? {
                popperRect: B.getBoundingClientRect(),
                popperState: ct,
                props: n,
              }
            : null;
        })
        .filter(Boolean);
      Os(F, d) && (W(), he(d));
    }
  }
  function Ve(d) {
    var w = qe(d) || (u.props.trigger.indexOf("click") >= 0 && a);
    if (!w) {
      if (u.props.interactive) {
        u.hideWithInteractivity(d);
        return;
      }
      he(d);
    }
  }
  function ie(d) {
    (u.props.trigger.indexOf("focusin") < 0 && d.target !== k()) ||
      (u.props.interactive && d.relatedTarget && m.contains(d.relatedTarget)) ||
      he(d);
  }
  function qe(d) {
    return Se.isTouch ? q() !== d.type.indexOf("touch") >= 0 : !1;
  }
  function Pe() {
    T();
    var d = u.props,
      w = d.popperOptions,
      R = d.placement,
      F = d.offset,
      B = d.getReferenceClientRect,
      oe = d.moveTransition,
      ge = z() ? $n(m).arrow : null,
      ct = B
        ? { getBoundingClientRect: B, contextElement: B.contextElement || k() }
        : e,
      Lr = {
        name: "$$tippy",
        enabled: !0,
        phase: "beforeWrite",
        requires: ["computeStyles"],
        fn: function (Kt) {
          var ut = Kt.state;
          if (z()) {
            var ca = Y(),
              On = ca.box;
            ["placement", "reference-hidden", "escaped"].forEach(function (zt) {
              zt === "placement"
                ? On.setAttribute("data-placement", ut.placement)
                : ut.attributes.popper["data-popper-" + zt]
                  ? On.setAttribute("data-" + zt, "")
                  : On.removeAttribute("data-" + zt);
            }),
              (ut.attributes.popper = {});
          }
        },
      },
      Ge = [
        { name: "offset", options: { offset: F } },
        {
          name: "preventOverflow",
          options: { padding: { top: 2, bottom: 2, left: 5, right: 5 } },
        },
        { name: "flip", options: { padding: 5 } },
        { name: "computeStyles", options: { adaptive: !oe } },
        Lr,
      ];
    z() &&
      ge &&
      Ge.push({ name: "arrow", options: { element: ge, padding: 3 } }),
      Ge.push.apply(Ge, (w == null ? void 0 : w.modifiers) || []),
      (u.popperInstance = ps(
        ct,
        m,
        Object.assign({}, w, { placement: R, onFirstUpdate: b, modifiers: Ge }),
      ));
  }
  function T() {
    u.popperInstance && (u.popperInstance.destroy(), (u.popperInstance = null));
  }
  function pe() {
    var d = u.props.appendTo,
      w,
      R = k();
    (u.props.interactive && d === Ni) || d === "parent"
      ? (w = R.parentNode)
      : (w = Li(d, [R])),
      w.contains(m) || w.appendChild(m),
      (u.state.isMounted = !0),
      Pe();
  }
  function V() {
    return on(m.querySelectorAll("[data-tippy-root]"));
  }
  function $(d) {
    u.clearDelayTimeouts(), d && y("onTrigger", [u, d]), G();
    var w = te(!0),
      R = H(),
      F = R[0],
      B = R[1];
    Se.isTouch && F === "hold" && B && (w = B),
      w
        ? (r = setTimeout(function () {
            u.show();
          }, w))
        : u.show();
  }
  function he(d) {
    if (
      (u.clearDelayTimeouts(), y("onUntrigger", [u, d]), !u.state.isVisible)
    ) {
      Z();
      return;
    }
    if (
      !(
        u.props.trigger.indexOf("mouseenter") >= 0 &&
        u.props.trigger.indexOf("click") >= 0 &&
        ["mouseleave", "mousemove"].indexOf(d.type) >= 0 &&
        a
      )
    ) {
      var w = te(!1);
      w
        ? (i = setTimeout(function () {
            u.state.isVisible && u.hide();
          }, w))
        : (o = requestAnimationFrame(function () {
            u.hide();
          }));
    }
  }
  function le() {
    u.state.isEnabled = !0;
  }
  function ke() {
    u.hide(), (u.state.isEnabled = !1);
  }
  function Be() {
    clearTimeout(r), clearTimeout(i), cancelAnimationFrame(o);
  }
  function g(d) {
    if (!u.state.isDestroyed) {
      y("onBeforeUpdate", [u, d]), st();
      var w = u.props,
        R = Yr(e, Object.assign({}, w, Vr(d), { ignoreAttributes: !0 }));
      (u.props = R),
        Le(),
        w.interactiveDebounce !== R.interactiveDebounce &&
          (W(), (A = Kr(Ie, R.interactiveDebounce))),
        w.triggerTarget && !R.triggerTarget
          ? ft(w.triggerTarget).forEach(function (F) {
              F.removeAttribute("aria-expanded");
            })
          : R.triggerTarget && e.removeAttribute("aria-expanded"),
        N(),
        p(),
        L && L(w, R),
        u.popperInstance &&
          (Pe(),
          V().forEach(function (F) {
            requestAnimationFrame(F._tippy.popperInstance.forceUpdate);
          })),
        y("onAfterUpdate", [u, d]);
    }
  }
  function x(d) {
    u.setProps({ content: d });
  }
  function D() {
    var d = u.state.isVisible,
      w = u.state.isDestroyed,
      R = !u.state.isEnabled,
      F = Se.isTouch && !u.props.touch,
      B = An(u.props.duration, 0, xe.duration);
    if (
      !(d || w || R || F) &&
      !k().hasAttribute("disabled") &&
      (y("onShow", [u], !1), u.props.onShow(u) !== !1)
    ) {
      if (
        ((u.state.isVisible = !0),
        z() && (m.style.visibility = "visible"),
        p(),
        G(),
        u.state.isMounted || (m.style.transition = "none"),
        z())
      ) {
        var oe = Y(),
          ge = oe.box,
          ct = oe.content;
        Sn([ge, ct], 0);
      }
      (b = function () {
        var Ge;
        if (!(!u.state.isVisible || f)) {
          if (
            ((f = !0),
            m.offsetHeight,
            (m.style.transition = u.props.moveTransition),
            z() && u.props.animation)
          ) {
            var wn = Y(),
              Kt = wn.box,
              ut = wn.content;
            Sn([Kt, ut], B), qr([Kt, ut], "visible");
          }
          C(),
            N(),
            zr(Mn, u),
            (Ge = u.popperInstance) == null || Ge.forceUpdate(),
            y("onMount", [u]),
            u.props.animation &&
              z() &&
              de(B, function () {
                (u.state.isShown = !0), y("onShown", [u]);
              });
        }
      }),
        pe();
    }
  }
  function j() {
    var d = !u.state.isVisible,
      w = u.state.isDestroyed,
      R = !u.state.isEnabled,
      F = An(u.props.duration, 1, xe.duration);
    if (!(d || w || R) && (y("onHide", [u], !1), u.props.onHide(u) !== !1)) {
      if (
        ((u.state.isVisible = !1),
        (u.state.isShown = !1),
        (f = !1),
        (a = !1),
        z() && (m.style.visibility = "hidden"),
        W(),
        Z(),
        p(!0),
        z())
      ) {
        var B = Y(),
          oe = B.box,
          ge = B.content;
        u.props.animation && (Sn([oe, ge], F), qr([oe, ge], "hidden"));
      }
      C(), N(), u.props.animation ? z() && Ce(F, u.unmount) : u.unmount();
    }
  }
  function ne(d) {
    U().addEventListener("mousemove", A), zr(qt, A), A(d);
  }
  function me() {
    u.state.isVisible && u.hide(),
      u.state.isMounted &&
        (T(),
        V().forEach(function (d) {
          d._tippy.unmount();
        }),
        m.parentNode && m.parentNode.removeChild(m),
        (Mn = Mn.filter(function (d) {
          return d !== u;
        })),
        (u.state.isMounted = !1),
        y("onHidden", [u]));
  }
  function En() {
    u.state.isDestroyed ||
      (u.clearDelayTimeouts(),
      u.unmount(),
      st(),
      delete e._tippy,
      (u.state.isDestroyed = !0),
      y("onDestroy", [u]));
  }
}
function jt(e, t) {
  t === void 0 && (t = {});
  var n = xe.plugins.concat(t.plugins || []);
  Ts();
  var r = Object.assign({}, t, { plugins: n }),
    i = Es(e),
    o = i.reduce(function (a, s) {
      var c = s && Bs(s, r);
      return c && a.push(c), a;
    }, []);
  return dn(e) ? o[0] : o;
}
jt.defaultProps = xe;
jt.setDefaultProps = Ns;
jt.currentInput = Se;
Object.assign({}, Ei, {
  effect: function (t) {
    var n = t.state,
      r = {
        popper: {
          position: n.options.strategy,
          left: "0",
          top: "0",
          margin: "0",
        },
        arrow: { position: "absolute" },
        reference: {},
      };
    Object.assign(n.elements.popper.style, r.popper),
      (n.styles = r),
      n.elements.arrow && Object.assign(n.elements.arrow.style, r.arrow);
  },
});
jt.setDefaultProps({ render: Bi });
var jn = !1,
  Hn = !1,
  Qe = [],
  Fn = -1;
function $s(e) {
  js(e);
}
function js(e) {
  Qe.includes(e) || Qe.push(e), Hs();
}
function $i(e) {
  let t = Qe.indexOf(e);
  t !== -1 && t > Fn && Qe.splice(t, 1);
}
function Hs() {
  !Hn && !jn && ((jn = !0), queueMicrotask(Fs));
}
function Fs() {
  (jn = !1), (Hn = !0);
  for (let e = 0; e < Qe.length; e++) Qe[e](), (Fn = e);
  (Qe.length = 0), (Fn = -1), (Hn = !1);
}
var yt,
  ot,
  mt,
  ji,
  Un = !0;
function Us(e) {
  (Un = !1), e(), (Un = !0);
}
function Ws(e) {
  (yt = e.reactive),
    (mt = e.release),
    (ot = (t) =>
      e.effect(t, {
        scheduler: (n) => {
          Un ? $s(n) : n();
        },
      })),
    (ji = e.raw);
}
function Qr(e) {
  ot = e;
}
function Ks(e) {
  let t = () => {};
  return [
    (r) => {
      let i = ot(r);
      return (
        e._x_effects ||
          ((e._x_effects = new Set()),
          (e._x_runEffects = () => {
            e._x_effects.forEach((o) => o());
          })),
        e._x_effects.add(i),
        (t = () => {
          i !== void 0 && (e._x_effects.delete(i), mt(i));
        }),
        i
      );
    },
    () => {
      t();
    },
  ];
}
function Hi(e, t) {
  let n = !0,
    r,
    i = ot(() => {
      let o = e();
      JSON.stringify(o),
        n
          ? (r = o)
          : queueMicrotask(() => {
              t(o, r), (r = o);
            }),
        (n = !1);
    });
  return () => mt(i);
}
var Fi = [],
  Ui = [],
  Wi = [];
function zs(e) {
  Wi.push(e);
}
function lr(e, t) {
  typeof t == "function"
    ? (e._x_cleanups || (e._x_cleanups = []), e._x_cleanups.push(t))
    : ((t = e), Ui.push(t));
}
function Ki(e) {
  Fi.push(e);
}
function zi(e, t, n) {
  e._x_attributeCleanups || (e._x_attributeCleanups = {}),
    e._x_attributeCleanups[t] || (e._x_attributeCleanups[t] = []),
    e._x_attributeCleanups[t].push(n);
}
function Vi(e, t) {
  e._x_attributeCleanups &&
    Object.entries(e._x_attributeCleanups).forEach(([n, r]) => {
      (t === void 0 || t.includes(n)) &&
        (r.forEach((i) => i()), delete e._x_attributeCleanups[n]);
    });
}
function Vs(e) {
  if (e._x_cleanups) for (; e._x_cleanups.length; ) e._x_cleanups.pop()();
}
var fr = new MutationObserver(gr),
  dr = !1;
function pr() {
  fr.observe(document, {
    subtree: !0,
    childList: !0,
    attributes: !0,
    attributeOldValue: !0,
  }),
    (dr = !0);
}
function qi() {
  qs(), fr.disconnect(), (dr = !1);
}
var wt = [];
function qs() {
  let e = fr.takeRecords();
  wt.push(() => e.length > 0 && gr(e));
  let t = wt.length;
  queueMicrotask(() => {
    if (wt.length === t) for (; wt.length > 0; ) wt.shift()();
  });
}
function ee(e) {
  if (!dr) return e();
  qi();
  let t = e();
  return pr(), t;
}
var hr = !1,
  an = [];
function Gs() {
  hr = !0;
}
function Xs() {
  (hr = !1), gr(an), (an = []);
}
function gr(e) {
  if (hr) {
    an = an.concat(e);
    return;
  }
  let t = new Set(),
    n = new Set(),
    r = new Map(),
    i = new Map();
  for (let o = 0; o < e.length; o++)
    if (
      !e[o].target._x_ignoreMutationObserver &&
      (e[o].type === "childList" &&
        (e[o].addedNodes.forEach((a) => a.nodeType === 1 && t.add(a)),
        e[o].removedNodes.forEach((a) => a.nodeType === 1 && n.add(a))),
      e[o].type === "attributes")
    ) {
      let a = e[o].target,
        s = e[o].attributeName,
        c = e[o].oldValue,
        f = () => {
          r.has(a) || r.set(a, []),
            r.get(a).push({ name: s, value: a.getAttribute(s) });
        },
        l = () => {
          i.has(a) || i.set(a, []), i.get(a).push(s);
        };
      a.hasAttribute(s) && c === null
        ? f()
        : a.hasAttribute(s)
          ? (l(), f())
          : l();
    }
  i.forEach((o, a) => {
    Vi(a, o);
  }),
    r.forEach((o, a) => {
      Fi.forEach((s) => s(a, o));
    });
  for (let o of n) t.has(o) || Ui.forEach((a) => a(o));
  t.forEach((o) => {
    (o._x_ignoreSelf = !0), (o._x_ignore = !0);
  });
  for (let o of t)
    n.has(o) ||
      (o.isConnected &&
        (delete o._x_ignoreSelf,
        delete o._x_ignore,
        Wi.forEach((a) => a(o)),
        (o._x_ignore = !0),
        (o._x_ignoreSelf = !0)));
  t.forEach((o) => {
    delete o._x_ignoreSelf, delete o._x_ignore;
  }),
    (t = null),
    (n = null),
    (r = null),
    (i = null);
}
function Gi(e) {
  return Ft(vt(e));
}
function Ht(e, t, n) {
  return (
    (e._x_dataStack = [t, ...vt(n || e)]),
    () => {
      e._x_dataStack = e._x_dataStack.filter((r) => r !== t);
    }
  );
}
function vt(e) {
  return e._x_dataStack
    ? e._x_dataStack
    : typeof ShadowRoot == "function" && e instanceof ShadowRoot
      ? vt(e.host)
      : e.parentNode
        ? vt(e.parentNode)
        : [];
}
function Ft(e) {
  return new Proxy({ objects: e }, Ys);
}
var Ys = {
  ownKeys({ objects: e }) {
    return Array.from(new Set(e.flatMap((t) => Object.keys(t))));
  },
  has({ objects: e }, t) {
    return t == Symbol.unscopables
      ? !1
      : e.some(
          (n) =>
            Object.prototype.hasOwnProperty.call(n, t) || Reflect.has(n, t),
        );
  },
  get({ objects: e }, t, n) {
    return t == "toJSON"
      ? Js
      : Reflect.get(e.find((r) => Reflect.has(r, t)) || {}, t, n);
  },
  set({ objects: e }, t, n, r) {
    const i =
        e.find((a) => Object.prototype.hasOwnProperty.call(a, t)) ||
        e[e.length - 1],
      o = Object.getOwnPropertyDescriptor(i, t);
    return o != null && o.set && o != null && o.get
      ? Reflect.set(i, t, n, r)
      : Reflect.set(i, t, n);
  },
};
function Js() {
  return Reflect.ownKeys(this).reduce(
    (t, n) => ((t[n] = Reflect.get(this, n)), t),
    {},
  );
}
function Xi(e) {
  let t = (r) => typeof r == "object" && !Array.isArray(r) && r !== null,
    n = (r, i = "") => {
      Object.entries(Object.getOwnPropertyDescriptors(r)).forEach(
        ([o, { value: a, enumerable: s }]) => {
          if (
            s === !1 ||
            a === void 0 ||
            (typeof a == "object" && a !== null && a.__v_skip)
          )
            return;
          let c = i === "" ? o : `${i}.${o}`;
          typeof a == "object" && a !== null && a._x_interceptor
            ? (r[o] = a.initialize(e, c, o))
            : t(a) && a !== r && !(a instanceof Element) && n(a, c);
        },
      );
    };
  return n(e);
}
function Yi(e, t = () => {}) {
  let n = {
    initialValue: void 0,
    _x_interceptor: !0,
    initialize(r, i, o) {
      return e(
        this.initialValue,
        () => Zs(r, i),
        (a) => Wn(r, i, a),
        i,
        o,
      );
    },
  };
  return (
    t(n),
    (r) => {
      if (typeof r == "object" && r !== null && r._x_interceptor) {
        let i = n.initialize.bind(n);
        n.initialize = (o, a, s) => {
          let c = r.initialize(o, a, s);
          return (n.initialValue = c), i(o, a, s);
        };
      } else n.initialValue = r;
      return n;
    }
  );
}
function Zs(e, t) {
  return t.split(".").reduce((n, r) => n[r], e);
}
function Wn(e, t, n) {
  if ((typeof t == "string" && (t = t.split(".")), t.length === 1)) e[t[0]] = n;
  else {
    if (t.length === 0) throw error;
    return e[t[0]] || (e[t[0]] = {}), Wn(e[t[0]], t.slice(1), n);
  }
}
var Ji = {};
function we(e, t) {
  Ji[e] = t;
}
function Kn(e, t) {
  return (
    Object.entries(Ji).forEach(([n, r]) => {
      let i = null;
      function o() {
        if (i) return i;
        {
          let [a, s] = ro(t);
          return (i = { interceptor: Yi, ...a }), lr(t, s), i;
        }
      }
      Object.defineProperty(e, `$${n}`, {
        get() {
          return r(t, o());
        },
        enumerable: !1,
      });
    }),
    e
  );
}
function Qs(e, t, n, ...r) {
  try {
    return n(...r);
  } catch (i) {
    Lt(i, e, t);
  }
}
function Lt(e, t, n = void 0) {
  (e = Object.assign(e ?? { message: "No error message given." }, {
    el: t,
    expression: n,
  })),
    console.warn(
      `Alpine Expression Error: ${e.message}

${
  n
    ? 'Expression: "' +
      n +
      `"

`
    : ""
}`,
      t,
    ),
    setTimeout(() => {
      throw e;
    }, 0);
}
var tn = !0;
function Zi(e) {
  let t = tn;
  tn = !1;
  let n = e();
  return (tn = t), n;
}
function et(e, t, n = {}) {
  let r;
  return ae(e, t)((i) => (r = i), n), r;
}
function ae(...e) {
  return Qi(...e);
}
var Qi = eo;
function ec(e) {
  Qi = e;
}
function eo(e, t) {
  let n = {};
  Kn(n, e);
  let r = [n, ...vt(e)],
    i = typeof t == "function" ? tc(r, t) : rc(r, t, e);
  return Qs.bind(null, e, t, i);
}
function tc(e, t) {
  return (n = () => {}, { scope: r = {}, params: i = [] } = {}) => {
    let o = t.apply(Ft([r, ...e]), i);
    sn(n, o);
  };
}
var Cn = {};
function nc(e, t) {
  if (Cn[e]) return Cn[e];
  let n = Object.getPrototypeOf(async function () {}).constructor,
    r =
      /^[\n\s]*if.*\(.*\)/.test(e.trim()) || /^(let|const)\s/.test(e.trim())
        ? `(async()=>{ ${e} })()`
        : e,
    o = (() => {
      try {
        let a = new n(
          ["__self", "scope"],
          `with (scope) { __self.result = ${r} }; __self.finished = true; return __self.result;`,
        );
        return Object.defineProperty(a, "name", { value: `[Alpine] ${e}` }), a;
      } catch (a) {
        return Lt(a, t, e), Promise.resolve();
      }
    })();
  return (Cn[e] = o), o;
}
function rc(e, t, n) {
  let r = nc(t, n);
  return (i = () => {}, { scope: o = {}, params: a = [] } = {}) => {
    (r.result = void 0), (r.finished = !1);
    let s = Ft([o, ...e]);
    if (typeof r == "function") {
      let c = r(r, s).catch((f) => Lt(f, n, t));
      r.finished
        ? (sn(i, r.result, s, a, n), (r.result = void 0))
        : c
            .then((f) => {
              sn(i, f, s, a, n);
            })
            .catch((f) => Lt(f, n, t))
            .finally(() => (r.result = void 0));
    }
  };
}
function sn(e, t, n, r, i) {
  if (tn && typeof t == "function") {
    let o = t.apply(n, r);
    o instanceof Promise
      ? o.then((a) => sn(e, a, n, r)).catch((a) => Lt(a, i, t))
      : e(o);
  } else
    typeof t == "object" && t instanceof Promise ? t.then((o) => e(o)) : e(t);
}
var vr = "x-";
function xt(e = "") {
  return vr + e;
}
function ic(e) {
  vr = e;
}
var cn = {};
function Q(e, t) {
  return (
    (cn[e] = t),
    {
      before(n) {
        if (!cn[n]) {
          console.warn(
            String.raw`Cannot find directive \`${n}\`. \`${e}\` will use the default order of execution`,
          );
          return;
        }
        const r = Je.indexOf(n);
        Je.splice(r >= 0 ? r : Je.indexOf("DEFAULT"), 0, e);
      },
    }
  );
}
function oc(e) {
  return Object.keys(cn).includes(e);
}
function _r(e, t, n) {
  if (((t = Array.from(t)), e._x_virtualDirectives)) {
    let o = Object.entries(e._x_virtualDirectives).map(([s, c]) => ({
        name: s,
        value: c,
      })),
      a = to(o);
    (o = o.map((s) =>
      a.find((c) => c.name === s.name)
        ? { name: `x-bind:${s.name}`, value: `"${s.value}"` }
        : s,
    )),
      (t = t.concat(o));
  }
  let r = {};
  return t
    .map(ao((o, a) => (r[o] = a)))
    .filter(co)
    .map(cc(r, n))
    .sort(uc)
    .map((o) => sc(e, o));
}
function to(e) {
  return Array.from(e)
    .map(ao())
    .filter((t) => !co(t));
}
var zn = !1,
  St = new Map(),
  no = Symbol();
function ac(e) {
  zn = !0;
  let t = Symbol();
  (no = t), St.set(t, []);
  let n = () => {
      for (; St.get(t).length; ) St.get(t).shift()();
      St.delete(t);
    },
    r = () => {
      (zn = !1), n();
    };
  e(n), r();
}
function ro(e) {
  let t = [],
    n = (s) => t.push(s),
    [r, i] = Ks(e);
  return (
    t.push(i),
    [
      {
        Alpine: Wt,
        effect: r,
        cleanup: n,
        evaluateLater: ae.bind(ae, e),
        evaluate: et.bind(et, e),
      },
      () => t.forEach((s) => s()),
    ]
  );
}
function sc(e, t) {
  let n = () => {},
    r = cn[t.type] || n,
    [i, o] = ro(e);
  zi(e, t.original, o);
  let a = () => {
    e._x_ignore ||
      e._x_ignoreSelf ||
      (r.inline && r.inline(e, t, i),
      (r = r.bind(r, e, t, i)),
      zn ? St.get(no).push(r) : r());
  };
  return (a.runCleanups = o), a;
}
var io =
    (e, t) =>
    ({ name: n, value: r }) => (
      n.startsWith(e) && (n = n.replace(e, t)), { name: n, value: r }
    ),
  oo = (e) => e;
function ao(e = () => {}) {
  return ({ name: t, value: n }) => {
    let { name: r, value: i } = so.reduce((o, a) => a(o), {
      name: t,
      value: n,
    });
    return r !== t && e(r, t), { name: r, value: i };
  };
}
var so = [];
function br(e) {
  so.push(e);
}
function co({ name: e }) {
  return uo().test(e);
}
var uo = () => new RegExp(`^${vr}([^:^.]+)\\b`);
function cc(e, t) {
  return ({ name: n, value: r }) => {
    let i = n.match(uo()),
      o = n.match(/:([a-zA-Z0-9\-_:]+)/),
      a = n.match(/\.[^.\]]+(?=[^\]]*$)/g) || [],
      s = t || e[n] || n;
    return {
      type: i ? i[1] : null,
      value: o ? o[1] : null,
      modifiers: a.map((c) => c.replace(".", "")),
      expression: r,
      original: s,
    };
  };
}
var Vn = "DEFAULT",
  Je = [
    "ignore",
    "ref",
    "data",
    "id",
    "anchor",
    "bind",
    "init",
    "for",
    "model",
    "modelable",
    "transition",
    "show",
    "if",
    Vn,
    "teleport",
  ];
function uc(e, t) {
  let n = Je.indexOf(e.type) === -1 ? Vn : e.type,
    r = Je.indexOf(t.type) === -1 ? Vn : t.type;
  return Je.indexOf(n) - Je.indexOf(r);
}
function Rt(e, t, n = {}) {
  e.dispatchEvent(
    new CustomEvent(t, {
      detail: n,
      bubbles: !0,
      composed: !0,
      cancelable: !0,
    }),
  );
}
function He(e, t) {
  if (typeof ShadowRoot == "function" && e instanceof ShadowRoot) {
    Array.from(e.children).forEach((i) => He(i, t));
    return;
  }
  let n = !1;
  if ((t(e, () => (n = !0)), n)) return;
  let r = e.firstElementChild;
  for (; r; ) He(r, t), (r = r.nextElementSibling);
}
function ve(e, ...t) {
  console.warn(`Alpine Warning: ${e}`, ...t);
}
var ei = !1;
function lc() {
  ei &&
    ve(
      "Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems.",
    ),
    (ei = !0),
    document.body ||
      ve(
        "Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?",
      ),
    Rt(document, "alpine:init"),
    Rt(document, "alpine:initializing"),
    pr(),
    zs((t) => Ne(t, He)),
    lr((t) => _o(t)),
    Ki((t, n) => {
      _r(t, n).forEach((r) => r());
    });
  let e = (t) => !pn(t.parentElement, !0);
  Array.from(document.querySelectorAll(po().join(",")))
    .filter(e)
    .forEach((t) => {
      Ne(t);
    }),
    Rt(document, "alpine:initialized"),
    setTimeout(() => {
      pc();
    });
}
var yr = [],
  lo = [];
function fo() {
  return yr.map((e) => e());
}
function po() {
  return yr.concat(lo).map((e) => e());
}
function ho(e) {
  yr.push(e);
}
function go(e) {
  lo.push(e);
}
function pn(e, t = !1) {
  return Ut(e, (n) => {
    if ((t ? po() : fo()).some((i) => n.matches(i))) return !0;
  });
}
function Ut(e, t) {
  if (e) {
    if (t(e)) return e;
    if ((e._x_teleportBack && (e = e._x_teleportBack), !!e.parentElement))
      return Ut(e.parentElement, t);
  }
}
function fc(e) {
  return fo().some((t) => e.matches(t));
}
var vo = [];
function dc(e) {
  vo.push(e);
}
function Ne(e, t = He, n = () => {}) {
  ac(() => {
    t(e, (r, i) => {
      n(r, i),
        vo.forEach((o) => o(r, i)),
        _r(r, r.attributes).forEach((o) => o()),
        r._x_ignore && i();
    });
  });
}
function _o(e, t = He) {
  t(e, (n) => {
    Vi(n), Vs(n);
  });
}
function pc() {
  [
    ["ui", "dialog", ["[x-dialog], [x-popover]"]],
    ["anchor", "anchor", ["[x-anchor]"]],
    ["sort", "sort", ["[x-sort]"]],
  ].forEach(([t, n, r]) => {
    oc(n) ||
      r.some((i) => {
        if (document.querySelector(i))
          return ve(`found "${i}", but missing ${t} plugin`), !0;
      });
  });
}
var qn = [],
  mr = !1;
function xr(e = () => {}) {
  return (
    queueMicrotask(() => {
      mr ||
        setTimeout(() => {
          Gn();
        });
    }),
    new Promise((t) => {
      qn.push(() => {
        e(), t();
      });
    })
  );
}
function Gn() {
  for (mr = !1; qn.length; ) qn.shift()();
}
function hc() {
  mr = !0;
}
function Er(e, t) {
  return Array.isArray(t)
    ? ti(e, t.join(" "))
    : typeof t == "object" && t !== null
      ? gc(e, t)
      : typeof t == "function"
        ? Er(e, t())
        : ti(e, t);
}
function ti(e, t) {
  let n = (i) =>
      i
        .split(" ")
        .filter((o) => !e.classList.contains(o))
        .filter(Boolean),
    r = (i) => (
      e.classList.add(...i),
      () => {
        e.classList.remove(...i);
      }
    );
  return (t = t === !0 ? (t = "") : t || ""), r(n(t));
}
function gc(e, t) {
  let n = (s) => s.split(" ").filter(Boolean),
    r = Object.entries(t)
      .flatMap(([s, c]) => (c ? n(s) : !1))
      .filter(Boolean),
    i = Object.entries(t)
      .flatMap(([s, c]) => (c ? !1 : n(s)))
      .filter(Boolean),
    o = [],
    a = [];
  return (
    i.forEach((s) => {
      e.classList.contains(s) && (e.classList.remove(s), a.push(s));
    }),
    r.forEach((s) => {
      e.classList.contains(s) || (e.classList.add(s), o.push(s));
    }),
    () => {
      a.forEach((s) => e.classList.add(s)),
        o.forEach((s) => e.classList.remove(s));
    }
  );
}
function hn(e, t) {
  return typeof t == "object" && t !== null ? vc(e, t) : _c(e, t);
}
function vc(e, t) {
  let n = {};
  return (
    Object.entries(t).forEach(([r, i]) => {
      (n[r] = e.style[r]),
        r.startsWith("--") || (r = bc(r)),
        e.style.setProperty(r, i);
    }),
    setTimeout(() => {
      e.style.length === 0 && e.removeAttribute("style");
    }),
    () => {
      hn(e, n);
    }
  );
}
function _c(e, t) {
  let n = e.getAttribute("style", t);
  return (
    e.setAttribute("style", t),
    () => {
      e.setAttribute("style", n || "");
    }
  );
}
function bc(e) {
  return e.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
}
function Xn(e, t = () => {}) {
  let n = !1;
  return function () {
    n ? t.apply(this, arguments) : ((n = !0), e.apply(this, arguments));
  };
}
Q(
  "transition",
  (e, { value: t, modifiers: n, expression: r }, { evaluate: i }) => {
    typeof r == "function" && (r = i(r)),
      r !== !1 && (!r || typeof r == "boolean" ? mc(e, n, t) : yc(e, r, t));
  },
);
function yc(e, t, n) {
  bo(e, Er, ""),
    {
      enter: (i) => {
        e._x_transition.enter.during = i;
      },
      "enter-start": (i) => {
        e._x_transition.enter.start = i;
      },
      "enter-end": (i) => {
        e._x_transition.enter.end = i;
      },
      leave: (i) => {
        e._x_transition.leave.during = i;
      },
      "leave-start": (i) => {
        e._x_transition.leave.start = i;
      },
      "leave-end": (i) => {
        e._x_transition.leave.end = i;
      },
    }[n](t);
}
function mc(e, t, n) {
  bo(e, hn);
  let r = !t.includes("in") && !t.includes("out") && !n,
    i = r || t.includes("in") || ["enter"].includes(n),
    o = r || t.includes("out") || ["leave"].includes(n);
  t.includes("in") && !r && (t = t.filter((E, O) => O < t.indexOf("out"))),
    t.includes("out") && !r && (t = t.filter((E, O) => O > t.indexOf("out")));
  let a = !t.includes("opacity") && !t.includes("scale"),
    s = a || t.includes("opacity"),
    c = a || t.includes("scale"),
    f = s ? 0 : 1,
    l = c ? Ot(t, "scale", 95) / 100 : 1,
    v = Ot(t, "delay", 0) / 1e3,
    b = Ot(t, "origin", "center"),
    _ = "opacity, transform",
    A = Ot(t, "duration", 150) / 1e3,
    S = Ot(t, "duration", 75) / 1e3,
    h = "cubic-bezier(0.4, 0.0, 0.2, 1)";
  i &&
    ((e._x_transition.enter.during = {
      transformOrigin: b,
      transitionDelay: `${v}s`,
      transitionProperty: _,
      transitionDuration: `${A}s`,
      transitionTimingFunction: h,
    }),
    (e._x_transition.enter.start = { opacity: f, transform: `scale(${l})` }),
    (e._x_transition.enter.end = { opacity: 1, transform: "scale(1)" })),
    o &&
      ((e._x_transition.leave.during = {
        transformOrigin: b,
        transitionDelay: `${v}s`,
        transitionProperty: _,
        transitionDuration: `${S}s`,
        transitionTimingFunction: h,
      }),
      (e._x_transition.leave.start = { opacity: 1, transform: "scale(1)" }),
      (e._x_transition.leave.end = { opacity: f, transform: `scale(${l})` }));
}
function bo(e, t, n = {}) {
  e._x_transition ||
    (e._x_transition = {
      enter: { during: n, start: n, end: n },
      leave: { during: n, start: n, end: n },
      in(r = () => {}, i = () => {}) {
        Yn(
          e,
          t,
          {
            during: this.enter.during,
            start: this.enter.start,
            end: this.enter.end,
          },
          r,
          i,
        );
      },
      out(r = () => {}, i = () => {}) {
        Yn(
          e,
          t,
          {
            during: this.leave.during,
            start: this.leave.start,
            end: this.leave.end,
          },
          r,
          i,
        );
      },
    });
}
window.Element.prototype._x_toggleAndCascadeWithTransitions = function (
  e,
  t,
  n,
  r,
) {
  const i =
    document.visibilityState === "visible" ? requestAnimationFrame : setTimeout;
  let o = () => i(n);
  if (t) {
    e._x_transition && (e._x_transition.enter || e._x_transition.leave)
      ? e._x_transition.enter &&
        (Object.entries(e._x_transition.enter.during).length ||
          Object.entries(e._x_transition.enter.start).length ||
          Object.entries(e._x_transition.enter.end).length)
        ? e._x_transition.in(n)
        : o()
      : e._x_transition
        ? e._x_transition.in(n)
        : o();
    return;
  }
  (e._x_hidePromise = e._x_transition
    ? new Promise((a, s) => {
        e._x_transition.out(
          () => {},
          () => a(r),
        ),
          e._x_transitioning &&
            e._x_transitioning.beforeCancel(() =>
              s({ isFromCancelledTransition: !0 }),
            );
      })
    : Promise.resolve(r)),
    queueMicrotask(() => {
      let a = yo(e);
      a
        ? (a._x_hideChildren || (a._x_hideChildren = []),
          a._x_hideChildren.push(e))
        : i(() => {
            let s = (c) => {
              let f = Promise.all([
                c._x_hidePromise,
                ...(c._x_hideChildren || []).map(s),
              ]).then(([l]) => l());
              return delete c._x_hidePromise, delete c._x_hideChildren, f;
            };
            s(e).catch((c) => {
              if (!c.isFromCancelledTransition) throw c;
            });
          });
    });
};
function yo(e) {
  let t = e.parentNode;
  if (t) return t._x_hidePromise ? t : yo(t);
}
function Yn(
  e,
  t,
  { during: n, start: r, end: i } = {},
  o = () => {},
  a = () => {},
) {
  if (
    (e._x_transitioning && e._x_transitioning.cancel(),
    Object.keys(n).length === 0 &&
      Object.keys(r).length === 0 &&
      Object.keys(i).length === 0)
  ) {
    o(), a();
    return;
  }
  let s, c, f;
  xc(e, {
    start() {
      s = t(e, r);
    },
    during() {
      c = t(e, n);
    },
    before: o,
    end() {
      s(), (f = t(e, i));
    },
    after: a,
    cleanup() {
      c(), f();
    },
  });
}
function xc(e, t) {
  let n,
    r,
    i,
    o = Xn(() => {
      ee(() => {
        (n = !0),
          r || t.before(),
          i || (t.end(), Gn()),
          t.after(),
          e.isConnected && t.cleanup(),
          delete e._x_transitioning;
      });
    });
  (e._x_transitioning = {
    beforeCancels: [],
    beforeCancel(a) {
      this.beforeCancels.push(a);
    },
    cancel: Xn(function () {
      for (; this.beforeCancels.length; ) this.beforeCancels.shift()();
      o();
    }),
    finish: o,
  }),
    ee(() => {
      t.start(), t.during();
    }),
    hc(),
    requestAnimationFrame(() => {
      if (n) return;
      let a =
          Number(
            getComputedStyle(e)
              .transitionDuration.replace(/,.*/, "")
              .replace("s", ""),
          ) * 1e3,
        s =
          Number(
            getComputedStyle(e)
              .transitionDelay.replace(/,.*/, "")
              .replace("s", ""),
          ) * 1e3;
      a === 0 &&
        (a =
          Number(getComputedStyle(e).animationDuration.replace("s", "")) * 1e3),
        ee(() => {
          t.before();
        }),
        (r = !0),
        requestAnimationFrame(() => {
          n ||
            (ee(() => {
              t.end();
            }),
            Gn(),
            setTimeout(e._x_transitioning.finish, a + s),
            (i = !0));
        });
    });
}
function Ot(e, t, n) {
  if (e.indexOf(t) === -1) return n;
  const r = e[e.indexOf(t) + 1];
  if (!r || (t === "scale" && isNaN(r))) return n;
  if (t === "duration" || t === "delay") {
    let i = r.match(/([0-9]+)ms/);
    if (i) return i[1];
  }
  return t === "origin" &&
    ["top", "right", "left", "center", "bottom"].includes(e[e.indexOf(t) + 2])
    ? [r, e[e.indexOf(t) + 2]].join(" ")
    : r;
}
var Fe = !1;
function Ke(e, t = () => {}) {
  return (...n) => (Fe ? t(...n) : e(...n));
}
function Ec(e) {
  return (...t) => Fe && e(...t);
}
var mo = [];
function gn(e) {
  mo.push(e);
}
function wc(e, t) {
  mo.forEach((n) => n(e, t)),
    (Fe = !0),
    xo(() => {
      Ne(t, (n, r) => {
        r(n, () => {});
      });
    }),
    (Fe = !1);
}
var Jn = !1;
function Oc(e, t) {
  t._x_dataStack || (t._x_dataStack = e._x_dataStack),
    (Fe = !0),
    (Jn = !0),
    xo(() => {
      Ac(t);
    }),
    (Fe = !1),
    (Jn = !1);
}
function Ac(e) {
  let t = !1;
  Ne(e, (r, i) => {
    He(r, (o, a) => {
      if (t && fc(o)) return a();
      (t = !0), i(o, a);
    });
  });
}
function xo(e) {
  let t = ot;
  Qr((n, r) => {
    let i = t(n);
    return mt(i), () => {};
  }),
    e(),
    Qr(t);
}
function Eo(e, t, n, r = []) {
  switch (
    (e._x_bindings || (e._x_bindings = yt({})),
    (e._x_bindings[t] = n),
    (t = r.includes("camel") ? Nc(t) : t),
    t)
  ) {
    case "value":
      Sc(e, n);
      break;
    case "style":
      Mc(e, n);
      break;
    case "class":
      Tc(e, n);
      break;
    case "selected":
    case "checked":
      Cc(e, t, n);
      break;
    default:
      wo(e, t, n);
      break;
  }
}
function Sc(e, t) {
  if (e.type === "radio")
    e.attributes.value === void 0 && (e.value = t),
      window.fromModel &&
        (typeof t == "boolean"
          ? (e.checked = nn(e.value) === t)
          : (e.checked = ni(e.value, t)));
  else if (e.type === "checkbox")
    Number.isInteger(t)
      ? (e.value = t)
      : !Array.isArray(t) &&
          typeof t != "boolean" &&
          ![null, void 0].includes(t)
        ? (e.value = String(t))
        : Array.isArray(t)
          ? (e.checked = t.some((n) => ni(n, e.value)))
          : (e.checked = !!t);
  else if (e.tagName === "SELECT") Dc(e, t);
  else {
    if (e.value === t) return;
    e.value = t === void 0 ? "" : t;
  }
}
function Tc(e, t) {
  e._x_undoAddedClasses && e._x_undoAddedClasses(),
    (e._x_undoAddedClasses = Er(e, t));
}
function Mc(e, t) {
  e._x_undoAddedStyles && e._x_undoAddedStyles(),
    (e._x_undoAddedStyles = hn(e, t));
}
function Cc(e, t, n) {
  wo(e, t, n), Ic(e, t, n);
}
function wo(e, t, n) {
  [null, void 0, !1].includes(n) && Lc(t)
    ? e.removeAttribute(t)
    : (Oo(t) && (n = t), Rc(e, t, n));
}
function Rc(e, t, n) {
  e.getAttribute(t) != n && e.setAttribute(t, n);
}
function Ic(e, t, n) {
  e[t] !== n && (e[t] = n);
}
function Dc(e, t) {
  const n = [].concat(t).map((r) => r + "");
  Array.from(e.options).forEach((r) => {
    r.selected = n.includes(r.value);
  });
}
function Nc(e) {
  return e.toLowerCase().replace(/-(\w)/g, (t, n) => n.toUpperCase());
}
function ni(e, t) {
  return e == t;
}
function nn(e) {
  return [1, "1", "true", "on", "yes", !0].includes(e)
    ? !0
    : [0, "0", "false", "off", "no", !1].includes(e)
      ? !1
      : e
        ? !!e
        : null;
}
function Oo(e) {
  return [
    "disabled",
    "checked",
    "required",
    "readonly",
    "open",
    "selected",
    "autofocus",
    "itemscope",
    "multiple",
    "novalidate",
    "allowfullscreen",
    "allowpaymentrequest",
    "formnovalidate",
    "autoplay",
    "controls",
    "loop",
    "muted",
    "playsinline",
    "default",
    "ismap",
    "reversed",
    "async",
    "defer",
    "nomodule",
  ].includes(e);
}
function Lc(e) {
  return ![
    "aria-pressed",
    "aria-checked",
    "aria-expanded",
    "aria-selected",
  ].includes(e);
}
function Pc(e, t, n) {
  return e._x_bindings && e._x_bindings[t] !== void 0
    ? e._x_bindings[t]
    : Ao(e, t, n);
}
function kc(e, t, n, r = !0) {
  if (e._x_bindings && e._x_bindings[t] !== void 0) return e._x_bindings[t];
  if (e._x_inlineBindings && e._x_inlineBindings[t] !== void 0) {
    let i = e._x_inlineBindings[t];
    return (i.extract = r), Zi(() => et(e, i.expression));
  }
  return Ao(e, t, n);
}
function Ao(e, t, n) {
  let r = e.getAttribute(t);
  return r === null
    ? typeof n == "function"
      ? n()
      : n
    : r === ""
      ? !0
      : Oo(t)
        ? !![t, "true"].includes(r)
        : r;
}
function So(e, t) {
  var n;
  return function () {
    var r = this,
      i = arguments,
      o = function () {
        (n = null), e.apply(r, i);
      };
    clearTimeout(n), (n = setTimeout(o, t));
  };
}
function To(e, t) {
  let n;
  return function () {
    let r = this,
      i = arguments;
    n || (e.apply(r, i), (n = !0), setTimeout(() => (n = !1), t));
  };
}
function Mo({ get: e, set: t }, { get: n, set: r }) {
  let i = !0,
    o,
    a = ot(() => {
      let s = e(),
        c = n();
      if (i) r(Rn(s)), (i = !1);
      else {
        let f = JSON.stringify(s),
          l = JSON.stringify(c);
        f !== o ? r(Rn(s)) : f !== l && t(Rn(c));
      }
      (o = JSON.stringify(e())), JSON.stringify(n());
    });
  return () => {
    mt(a);
  };
}
function Rn(e) {
  return typeof e == "object" ? JSON.parse(JSON.stringify(e)) : e;
}
function Bc(e) {
  (Array.isArray(e) ? e : [e]).forEach((n) => n(Wt));
}
var Ye = {},
  ri = !1;
function $c(e, t) {
  if ((ri || ((Ye = yt(Ye)), (ri = !0)), t === void 0)) return Ye[e];
  (Ye[e] = t),
    typeof t == "object" &&
      t !== null &&
      t.hasOwnProperty("init") &&
      typeof t.init == "function" &&
      Ye[e].init(),
    Xi(Ye[e]);
}
function jc() {
  return Ye;
}
var Co = {};
function Hc(e, t) {
  let n = typeof t != "function" ? () => t : t;
  return e instanceof Element ? Ro(e, n()) : ((Co[e] = n), () => {});
}
function Fc(e) {
  return (
    Object.entries(Co).forEach(([t, n]) => {
      Object.defineProperty(e, t, {
        get() {
          return (...r) => n(...r);
        },
      });
    }),
    e
  );
}
function Ro(e, t, n) {
  let r = [];
  for (; r.length; ) r.pop()();
  let i = Object.entries(t).map(([a, s]) => ({ name: a, value: s })),
    o = to(i);
  return (
    (i = i.map((a) =>
      o.find((s) => s.name === a.name)
        ? { name: `x-bind:${a.name}`, value: `"${a.value}"` }
        : a,
    )),
    _r(e, i, n).map((a) => {
      r.push(a.runCleanups), a();
    }),
    () => {
      for (; r.length; ) r.pop()();
    }
  );
}
var Io = {};
function Uc(e, t) {
  Io[e] = t;
}
function Wc(e, t) {
  return (
    Object.entries(Io).forEach(([n, r]) => {
      Object.defineProperty(e, n, {
        get() {
          return (...i) => r.bind(t)(...i);
        },
        enumerable: !1,
      });
    }),
    e
  );
}
var Kc = {
    get reactive() {
      return yt;
    },
    get release() {
      return mt;
    },
    get effect() {
      return ot;
    },
    get raw() {
      return ji;
    },
    version: "3.13.10",
    flushAndStopDeferringMutations: Xs,
    dontAutoEvaluateFunctions: Zi,
    disableEffectScheduling: Us,
    startObservingMutations: pr,
    stopObservingMutations: qi,
    setReactivityEngine: Ws,
    onAttributeRemoved: zi,
    onAttributesAdded: Ki,
    closestDataStack: vt,
    skipDuringClone: Ke,
    onlyDuringClone: Ec,
    addRootSelector: ho,
    addInitSelector: go,
    interceptClone: gn,
    addScopeToNode: Ht,
    deferMutations: Gs,
    mapAttributes: br,
    evaluateLater: ae,
    interceptInit: dc,
    setEvaluator: ec,
    mergeProxies: Ft,
    extractProp: kc,
    findClosest: Ut,
    onElRemoved: lr,
    closestRoot: pn,
    destroyTree: _o,
    interceptor: Yi,
    transition: Yn,
    setStyles: hn,
    mutateDom: ee,
    directive: Q,
    entangle: Mo,
    throttle: To,
    debounce: So,
    evaluate: et,
    initTree: Ne,
    nextTick: xr,
    prefixed: xt,
    prefix: ic,
    plugin: Bc,
    magic: we,
    store: $c,
    start: lc,
    clone: Oc,
    cloneNode: wc,
    bound: Pc,
    $data: Gi,
    watch: Hi,
    walk: He,
    data: Uc,
    bind: Hc,
  },
  Wt = Kc;
function zc(e, t) {
  const n = Object.create(null),
    r = e.split(",");
  for (let i = 0; i < r.length; i++) n[r[i]] = !0;
  return (i) => !!n[i];
}
var Vc = Object.freeze({}),
  qc = Object.prototype.hasOwnProperty,
  vn = (e, t) => qc.call(e, t),
  tt = Array.isArray,
  It = (e) => Do(e) === "[object Map]",
  Gc = (e) => typeof e == "string",
  wr = (e) => typeof e == "symbol",
  _n = (e) => e !== null && typeof e == "object",
  Xc = Object.prototype.toString,
  Do = (e) => Xc.call(e),
  No = (e) => Do(e).slice(8, -1),
  Or = (e) =>
    Gc(e) && e !== "NaN" && e[0] !== "-" && "" + parseInt(e, 10) === e,
  Yc = (e) => {
    const t = Object.create(null);
    return (n) => t[n] || (t[n] = e(n));
  },
  Jc = Yc((e) => e.charAt(0).toUpperCase() + e.slice(1)),
  Lo = (e, t) => e !== t && (e === e || t === t),
  Zn = new WeakMap(),
  At = [],
  Ae,
  nt = Symbol("iterate"),
  Qn = Symbol("Map key iterate");
function Zc(e) {
  return e && e._isEffect === !0;
}
function Qc(e, t = Vc) {
  Zc(e) && (e = e.raw);
  const n = nu(e, t);
  return t.lazy || n(), n;
}
function eu(e) {
  e.active && (Po(e), e.options.onStop && e.options.onStop(), (e.active = !1));
}
var tu = 0;
function nu(e, t) {
  const n = function () {
    if (!n.active) return e();
    if (!At.includes(n)) {
      Po(n);
      try {
        return iu(), At.push(n), (Ae = n), e();
      } finally {
        At.pop(), ko(), (Ae = At[At.length - 1]);
      }
    }
  };
  return (
    (n.id = tu++),
    (n.allowRecurse = !!t.allowRecurse),
    (n._isEffect = !0),
    (n.active = !0),
    (n.raw = e),
    (n.deps = []),
    (n.options = t),
    n
  );
}
function Po(e) {
  const { deps: t } = e;
  if (t.length) {
    for (let n = 0; n < t.length; n++) t[n].delete(e);
    t.length = 0;
  }
}
var _t = !0,
  Ar = [];
function ru() {
  Ar.push(_t), (_t = !1);
}
function iu() {
  Ar.push(_t), (_t = !0);
}
function ko() {
  const e = Ar.pop();
  _t = e === void 0 ? !0 : e;
}
function Ee(e, t, n) {
  if (!_t || Ae === void 0) return;
  let r = Zn.get(e);
  r || Zn.set(e, (r = new Map()));
  let i = r.get(n);
  i || r.set(n, (i = new Set())),
    i.has(Ae) ||
      (i.add(Ae),
      Ae.deps.push(i),
      Ae.options.onTrack &&
        Ae.options.onTrack({ effect: Ae, target: e, type: t, key: n }));
}
function Ue(e, t, n, r, i, o) {
  const a = Zn.get(e);
  if (!a) return;
  const s = new Set(),
    c = (l) => {
      l &&
        l.forEach((v) => {
          (v !== Ae || v.allowRecurse) && s.add(v);
        });
    };
  if (t === "clear") a.forEach(c);
  else if (n === "length" && tt(e))
    a.forEach((l, v) => {
      (v === "length" || v >= r) && c(l);
    });
  else
    switch ((n !== void 0 && c(a.get(n)), t)) {
      case "add":
        tt(e)
          ? Or(n) && c(a.get("length"))
          : (c(a.get(nt)), It(e) && c(a.get(Qn)));
        break;
      case "delete":
        tt(e) || (c(a.get(nt)), It(e) && c(a.get(Qn)));
        break;
      case "set":
        It(e) && c(a.get(nt));
        break;
    }
  const f = (l) => {
    l.options.onTrigger &&
      l.options.onTrigger({
        effect: l,
        target: e,
        key: n,
        type: t,
        newValue: r,
        oldValue: i,
        oldTarget: o,
      }),
      l.options.scheduler ? l.options.scheduler(l) : l();
  };
  s.forEach(f);
}
var ou = zc("__proto__,__v_isRef,__isVue"),
  Bo = new Set(
    Object.getOwnPropertyNames(Symbol)
      .map((e) => Symbol[e])
      .filter(wr),
  ),
  au = $o(),
  su = $o(!0),
  ii = cu();
function cu() {
  const e = {};
  return (
    ["includes", "indexOf", "lastIndexOf"].forEach((t) => {
      e[t] = function (...n) {
        const r = X(this);
        for (let o = 0, a = this.length; o < a; o++) Ee(r, "get", o + "");
        const i = r[t](...n);
        return i === -1 || i === !1 ? r[t](...n.map(X)) : i;
      };
    }),
    ["push", "pop", "shift", "unshift", "splice"].forEach((t) => {
      e[t] = function (...n) {
        ru();
        const r = X(this)[t].apply(this, n);
        return ko(), r;
      };
    }),
    e
  );
}
function $o(e = !1, t = !1) {
  return function (r, i, o) {
    if (i === "__v_isReactive") return !e;
    if (i === "__v_isReadonly") return e;
    if (i === "__v_raw" && o === (e ? (t ? Eu : Uo) : t ? xu : Fo).get(r))
      return r;
    const a = tt(r);
    if (!e && a && vn(ii, i)) return Reflect.get(ii, i, o);
    const s = Reflect.get(r, i, o);
    return (wr(i) ? Bo.has(i) : ou(i)) || (e || Ee(r, "get", i), t)
      ? s
      : er(s)
        ? !a || !Or(i)
          ? s.value
          : s
        : _n(s)
          ? e
            ? Wo(s)
            : Cr(s)
          : s;
  };
}
var uu = lu();
function lu(e = !1) {
  return function (n, r, i, o) {
    let a = n[r];
    if (!e && ((i = X(i)), (a = X(a)), !tt(n) && er(a) && !er(i)))
      return (a.value = i), !0;
    const s = tt(n) && Or(r) ? Number(r) < n.length : vn(n, r),
      c = Reflect.set(n, r, i, o);
    return (
      n === X(o) &&
        (s ? Lo(i, a) && Ue(n, "set", r, i, a) : Ue(n, "add", r, i)),
      c
    );
  };
}
function fu(e, t) {
  const n = vn(e, t),
    r = e[t],
    i = Reflect.deleteProperty(e, t);
  return i && n && Ue(e, "delete", t, void 0, r), i;
}
function du(e, t) {
  const n = Reflect.has(e, t);
  return (!wr(t) || !Bo.has(t)) && Ee(e, "has", t), n;
}
function pu(e) {
  return Ee(e, "iterate", tt(e) ? "length" : nt), Reflect.ownKeys(e);
}
var hu = { get: au, set: uu, deleteProperty: fu, has: du, ownKeys: pu },
  gu = {
    get: su,
    set(e, t) {
      return (
        console.warn(
          `Set operation on key "${String(t)}" failed: target is readonly.`,
          e,
        ),
        !0
      );
    },
    deleteProperty(e, t) {
      return (
        console.warn(
          `Delete operation on key "${String(t)}" failed: target is readonly.`,
          e,
        ),
        !0
      );
    },
  },
  Sr = (e) => (_n(e) ? Cr(e) : e),
  Tr = (e) => (_n(e) ? Wo(e) : e),
  Mr = (e) => e,
  bn = (e) => Reflect.getPrototypeOf(e);
function Gt(e, t, n = !1, r = !1) {
  e = e.__v_raw;
  const i = X(e),
    o = X(t);
  t !== o && !n && Ee(i, "get", t), !n && Ee(i, "get", o);
  const { has: a } = bn(i),
    s = r ? Mr : n ? Tr : Sr;
  if (a.call(i, t)) return s(e.get(t));
  if (a.call(i, o)) return s(e.get(o));
  e !== i && e.get(t);
}
function Xt(e, t = !1) {
  const n = this.__v_raw,
    r = X(n),
    i = X(e);
  return (
    e !== i && !t && Ee(r, "has", e),
    !t && Ee(r, "has", i),
    e === i ? n.has(e) : n.has(e) || n.has(i)
  );
}
function Yt(e, t = !1) {
  return (
    (e = e.__v_raw), !t && Ee(X(e), "iterate", nt), Reflect.get(e, "size", e)
  );
}
function oi(e) {
  e = X(e);
  const t = X(this);
  return bn(t).has.call(t, e) || (t.add(e), Ue(t, "add", e, e)), this;
}
function ai(e, t) {
  t = X(t);
  const n = X(this),
    { has: r, get: i } = bn(n);
  let o = r.call(n, e);
  o ? Ho(n, r, e) : ((e = X(e)), (o = r.call(n, e)));
  const a = i.call(n, e);
  return (
    n.set(e, t),
    o ? Lo(t, a) && Ue(n, "set", e, t, a) : Ue(n, "add", e, t),
    this
  );
}
function si(e) {
  const t = X(this),
    { has: n, get: r } = bn(t);
  let i = n.call(t, e);
  i ? Ho(t, n, e) : ((e = X(e)), (i = n.call(t, e)));
  const o = r ? r.call(t, e) : void 0,
    a = t.delete(e);
  return i && Ue(t, "delete", e, void 0, o), a;
}
function ci() {
  const e = X(this),
    t = e.size !== 0,
    n = It(e) ? new Map(e) : new Set(e),
    r = e.clear();
  return t && Ue(e, "clear", void 0, void 0, n), r;
}
function Jt(e, t) {
  return function (r, i) {
    const o = this,
      a = o.__v_raw,
      s = X(a),
      c = t ? Mr : e ? Tr : Sr;
    return (
      !e && Ee(s, "iterate", nt), a.forEach((f, l) => r.call(i, c(f), c(l), o))
    );
  };
}
function Zt(e, t, n) {
  return function (...r) {
    const i = this.__v_raw,
      o = X(i),
      a = It(o),
      s = e === "entries" || (e === Symbol.iterator && a),
      c = e === "keys" && a,
      f = i[e](...r),
      l = n ? Mr : t ? Tr : Sr;
    return (
      !t && Ee(o, "iterate", c ? Qn : nt),
      {
        next() {
          const { value: v, done: b } = f.next();
          return b
            ? { value: v, done: b }
            : { value: s ? [l(v[0]), l(v[1])] : l(v), done: b };
        },
        [Symbol.iterator]() {
          return this;
        },
      }
    );
  };
}
function $e(e) {
  return function (...t) {
    {
      const n = t[0] ? `on key "${t[0]}" ` : "";
      console.warn(
        `${Jc(e)} operation ${n}failed: target is readonly.`,
        X(this),
      );
    }
    return e === "delete" ? !1 : this;
  };
}
function vu() {
  const e = {
      get(o) {
        return Gt(this, o);
      },
      get size() {
        return Yt(this);
      },
      has: Xt,
      add: oi,
      set: ai,
      delete: si,
      clear: ci,
      forEach: Jt(!1, !1),
    },
    t = {
      get(o) {
        return Gt(this, o, !1, !0);
      },
      get size() {
        return Yt(this);
      },
      has: Xt,
      add: oi,
      set: ai,
      delete: si,
      clear: ci,
      forEach: Jt(!1, !0),
    },
    n = {
      get(o) {
        return Gt(this, o, !0);
      },
      get size() {
        return Yt(this, !0);
      },
      has(o) {
        return Xt.call(this, o, !0);
      },
      add: $e("add"),
      set: $e("set"),
      delete: $e("delete"),
      clear: $e("clear"),
      forEach: Jt(!0, !1),
    },
    r = {
      get(o) {
        return Gt(this, o, !0, !0);
      },
      get size() {
        return Yt(this, !0);
      },
      has(o) {
        return Xt.call(this, o, !0);
      },
      add: $e("add"),
      set: $e("set"),
      delete: $e("delete"),
      clear: $e("clear"),
      forEach: Jt(!0, !0),
    };
  return (
    ["keys", "values", "entries", Symbol.iterator].forEach((o) => {
      (e[o] = Zt(o, !1, !1)),
        (n[o] = Zt(o, !0, !1)),
        (t[o] = Zt(o, !1, !0)),
        (r[o] = Zt(o, !0, !0));
    }),
    [e, n, t, r]
  );
}
var [_u, bu, jl, Hl] = vu();
function jo(e, t) {
  const n = e ? bu : _u;
  return (r, i, o) =>
    i === "__v_isReactive"
      ? !e
      : i === "__v_isReadonly"
        ? e
        : i === "__v_raw"
          ? r
          : Reflect.get(vn(n, i) && i in r ? n : r, i, o);
}
var yu = { get: jo(!1) },
  mu = { get: jo(!0) };
function Ho(e, t, n) {
  const r = X(n);
  if (r !== n && t.call(e, r)) {
    const i = No(e);
    console.warn(
      `Reactive ${i} contains both the raw and reactive versions of the same object${i === "Map" ? " as keys" : ""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`,
    );
  }
}
var Fo = new WeakMap(),
  xu = new WeakMap(),
  Uo = new WeakMap(),
  Eu = new WeakMap();
function wu(e) {
  switch (e) {
    case "Object":
    case "Array":
      return 1;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2;
    default:
      return 0;
  }
}
function Ou(e) {
  return e.__v_skip || !Object.isExtensible(e) ? 0 : wu(No(e));
}
function Cr(e) {
  return e && e.__v_isReadonly ? e : Ko(e, !1, hu, yu, Fo);
}
function Wo(e) {
  return Ko(e, !0, gu, mu, Uo);
}
function Ko(e, t, n, r, i) {
  if (!_n(e))
    return console.warn(`value cannot be made reactive: ${String(e)}`), e;
  if (e.__v_raw && !(t && e.__v_isReactive)) return e;
  const o = i.get(e);
  if (o) return o;
  const a = Ou(e);
  if (a === 0) return e;
  const s = new Proxy(e, a === 2 ? r : n);
  return i.set(e, s), s;
}
function X(e) {
  return (e && X(e.__v_raw)) || e;
}
function er(e) {
  return !!(e && e.__v_isRef === !0);
}
we("nextTick", () => xr);
we("dispatch", (e) => Rt.bind(Rt, e));
we("watch", (e, { evaluateLater: t, cleanup: n }) => (r, i) => {
  let o = t(r),
    s = Hi(() => {
      let c;
      return o((f) => (c = f)), c;
    }, i);
  n(s);
});
we("store", jc);
we("data", (e) => Gi(e));
we("root", (e) => pn(e));
we(
  "refs",
  (e) => (e._x_refs_proxy || (e._x_refs_proxy = Ft(Au(e))), e._x_refs_proxy),
);
function Au(e) {
  let t = [];
  return (
    Ut(e, (n) => {
      n._x_refs && t.push(n._x_refs);
    }),
    t
  );
}
var In = {};
function zo(e) {
  return In[e] || (In[e] = 0), ++In[e];
}
function Su(e, t) {
  return Ut(e, (n) => {
    if (n._x_ids && n._x_ids[t]) return !0;
  });
}
function Tu(e, t) {
  e._x_ids || (e._x_ids = {}), e._x_ids[t] || (e._x_ids[t] = zo(t));
}
we("id", (e, { cleanup: t }) => (n, r = null) => {
  let i = `${n}${r ? `-${r}` : ""}`;
  return Mu(e, i, t, () => {
    let o = Su(e, n),
      a = o ? o._x_ids[n] : zo(n);
    return r ? `${n}-${a}-${r}` : `${n}-${a}`;
  });
});
gn((e, t) => {
  e._x_id && (t._x_id = e._x_id);
});
function Mu(e, t, n, r) {
  if ((e._x_id || (e._x_id = {}), e._x_id[t])) return e._x_id[t];
  let i = r();
  return (
    (e._x_id[t] = i),
    n(() => {
      delete e._x_id[t];
    }),
    i
  );
}
we("el", (e) => e);
Vo("Focus", "focus", "focus");
Vo("Persist", "persist", "persist");
function Vo(e, t, n) {
  we(t, (r) =>
    ve(
      `You can't use [$${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${n}`,
      r,
    ),
  );
}
Q(
  "modelable",
  (e, { expression: t }, { effect: n, evaluateLater: r, cleanup: i }) => {
    let o = r(t),
      a = () => {
        let l;
        return o((v) => (l = v)), l;
      },
      s = r(`${t} = __placeholder`),
      c = (l) => s(() => {}, { scope: { __placeholder: l } }),
      f = a();
    c(f),
      queueMicrotask(() => {
        if (!e._x_model) return;
        e._x_removeModelListeners.default();
        let l = e._x_model.get,
          v = e._x_model.set,
          b = Mo(
            {
              get() {
                return l();
              },
              set(_) {
                v(_);
              },
            },
            {
              get() {
                return a();
              },
              set(_) {
                c(_);
              },
            },
          );
        i(b);
      });
  },
);
Q("teleport", (e, { modifiers: t, expression: n }, { cleanup: r }) => {
  e.tagName.toLowerCase() !== "template" &&
    ve("x-teleport can only be used on a <template> tag", e);
  let i = ui(n),
    o = e.content.cloneNode(!0).firstElementChild;
  (e._x_teleport = o),
    (o._x_teleportBack = e),
    e.setAttribute("data-teleport-template", !0),
    o.setAttribute("data-teleport-target", !0),
    e._x_forwardEvents &&
      e._x_forwardEvents.forEach((s) => {
        o.addEventListener(s, (c) => {
          c.stopPropagation(), e.dispatchEvent(new c.constructor(c.type, c));
        });
      }),
    Ht(o, {}, e);
  let a = (s, c, f) => {
    f.includes("prepend")
      ? c.parentNode.insertBefore(s, c)
      : f.includes("append")
        ? c.parentNode.insertBefore(s, c.nextSibling)
        : c.appendChild(s);
  };
  ee(() => {
    a(o, i, t),
      Ke(() => {
        Ne(o), (o._x_ignore = !0);
      })();
  }),
    (e._x_teleportPutBack = () => {
      let s = ui(n);
      ee(() => {
        a(e._x_teleport, s, t);
      });
    }),
    r(() => o.remove());
});
var Cu = document.createElement("div");
function ui(e) {
  let t = Ke(
    () => document.querySelector(e),
    () => Cu,
  )();
  return t || ve(`Cannot find x-teleport element for selector: "${e}"`), t;
}
var qo = () => {};
qo.inline = (e, { modifiers: t }, { cleanup: n }) => {
  t.includes("self") ? (e._x_ignoreSelf = !0) : (e._x_ignore = !0),
    n(() => {
      t.includes("self") ? delete e._x_ignoreSelf : delete e._x_ignore;
    });
};
Q("ignore", qo);
Q(
  "effect",
  Ke((e, { expression: t }, { effect: n }) => {
    n(ae(e, t));
  }),
);
function tr(e, t, n, r) {
  let i = e,
    o = (c) => r(c),
    a = {},
    s = (c, f) => (l) => f(c, l);
  if (
    (n.includes("dot") && (t = Ru(t)),
    n.includes("camel") && (t = Iu(t)),
    n.includes("passive") && (a.passive = !0),
    n.includes("capture") && (a.capture = !0),
    n.includes("window") && (i = window),
    n.includes("document") && (i = document),
    n.includes("debounce"))
  ) {
    let c = n[n.indexOf("debounce") + 1] || "invalid-wait",
      f = un(c.split("ms")[0]) ? Number(c.split("ms")[0]) : 250;
    o = So(o, f);
  }
  if (n.includes("throttle")) {
    let c = n[n.indexOf("throttle") + 1] || "invalid-wait",
      f = un(c.split("ms")[0]) ? Number(c.split("ms")[0]) : 250;
    o = To(o, f);
  }
  return (
    n.includes("prevent") &&
      (o = s(o, (c, f) => {
        f.preventDefault(), c(f);
      })),
    n.includes("stop") &&
      (o = s(o, (c, f) => {
        f.stopPropagation(), c(f);
      })),
    n.includes("once") &&
      (o = s(o, (c, f) => {
        c(f), i.removeEventListener(t, o, a);
      })),
    (n.includes("away") || n.includes("outside")) &&
      ((i = document),
      (o = s(o, (c, f) => {
        e.contains(f.target) ||
          (f.target.isConnected !== !1 &&
            ((e.offsetWidth < 1 && e.offsetHeight < 1) ||
              (e._x_isShown !== !1 && c(f))));
      }))),
    n.includes("self") &&
      (o = s(o, (c, f) => {
        f.target === e && c(f);
      })),
    (o = s(o, (c, f) => {
      (Nu(t) && Lu(f, n)) || c(f);
    })),
    i.addEventListener(t, o, a),
    () => {
      i.removeEventListener(t, o, a);
    }
  );
}
function Ru(e) {
  return e.replace(/-/g, ".");
}
function Iu(e) {
  return e.toLowerCase().replace(/-(\w)/g, (t, n) => n.toUpperCase());
}
function un(e) {
  return !Array.isArray(e) && !isNaN(e);
}
function Du(e) {
  return [" ", "_"].includes(e)
    ? e
    : e
        .replace(/([a-z])([A-Z])/g, "$1-$2")
        .replace(/[_\s]/, "-")
        .toLowerCase();
}
function Nu(e) {
  return ["keydown", "keyup"].includes(e);
}
function Lu(e, t) {
  let n = t.filter(
    (o) =>
      !["window", "document", "prevent", "stop", "once", "capture"].includes(o),
  );
  if (n.includes("debounce")) {
    let o = n.indexOf("debounce");
    n.splice(o, un((n[o + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
  }
  if (n.includes("throttle")) {
    let o = n.indexOf("throttle");
    n.splice(o, un((n[o + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1);
  }
  if (n.length === 0 || (n.length === 1 && li(e.key).includes(n[0]))) return !1;
  const i = ["ctrl", "shift", "alt", "meta", "cmd", "super"].filter((o) =>
    n.includes(o),
  );
  return (
    (n = n.filter((o) => !i.includes(o))),
    !(
      i.length > 0 &&
      i.filter(
        (a) => ((a === "cmd" || a === "super") && (a = "meta"), e[`${a}Key`]),
      ).length === i.length &&
      li(e.key).includes(n[0])
    )
  );
}
function li(e) {
  if (!e) return [];
  e = Du(e);
  let t = {
    ctrl: "control",
    slash: "/",
    space: " ",
    spacebar: " ",
    cmd: "meta",
    esc: "escape",
    up: "arrow-up",
    down: "arrow-down",
    left: "arrow-left",
    right: "arrow-right",
    period: ".",
    comma: ",",
    equal: "=",
    minus: "-",
    underscore: "_",
  };
  return (
    (t[e] = e),
    Object.keys(t)
      .map((n) => {
        if (t[n] === e) return n;
      })
      .filter((n) => n)
  );
}
Q("model", (e, { modifiers: t, expression: n }, { effect: r, cleanup: i }) => {
  let o = e;
  t.includes("parent") && (o = e.parentNode);
  let a = ae(o, n),
    s;
  typeof n == "string"
    ? (s = ae(o, `${n} = __placeholder`))
    : typeof n == "function" && typeof n() == "string"
      ? (s = ae(o, `${n()} = __placeholder`))
      : (s = () => {});
  let c = () => {
      let b;
      return a((_) => (b = _)), fi(b) ? b.get() : b;
    },
    f = (b) => {
      let _;
      a((A) => (_ = A)),
        fi(_) ? _.set(b) : s(() => {}, { scope: { __placeholder: b } });
    };
  typeof n == "string" &&
    e.type === "radio" &&
    ee(() => {
      e.hasAttribute("name") || e.setAttribute("name", n);
    });
  var l =
    e.tagName.toLowerCase() === "select" ||
    ["checkbox", "radio"].includes(e.type) ||
    t.includes("lazy")
      ? "change"
      : "input";
  let v = Fe
    ? () => {}
    : tr(e, l, t, (b) => {
        f(Dn(e, t, b, c()));
      });
  if (
    (t.includes("fill") &&
      ([void 0, null, ""].includes(c()) ||
        (e.type === "checkbox" && Array.isArray(c())) ||
        (e.tagName.toLowerCase() === "select" && e.multiple)) &&
      f(Dn(e, t, { target: e }, c())),
    e._x_removeModelListeners || (e._x_removeModelListeners = {}),
    (e._x_removeModelListeners.default = v),
    i(() => e._x_removeModelListeners.default()),
    e.form)
  ) {
    let b = tr(e.form, "reset", [], (_) => {
      xr(() => e._x_model && e._x_model.set(Dn(e, t, { target: e }, c())));
    });
    i(() => b());
  }
  (e._x_model = {
    get() {
      return c();
    },
    set(b) {
      f(b);
    },
  }),
    (e._x_forceModelUpdate = (b) => {
      b === void 0 && typeof n == "string" && n.match(/\./) && (b = ""),
        (window.fromModel = !0),
        ee(() => Eo(e, "value", b)),
        delete window.fromModel;
    }),
    r(() => {
      let b = c();
      (t.includes("unintrusive") && document.activeElement.isSameNode(e)) ||
        e._x_forceModelUpdate(b);
    });
});
function Dn(e, t, n, r) {
  return ee(() => {
    if (n instanceof CustomEvent && n.detail !== void 0)
      return n.detail !== null && n.detail !== void 0
        ? n.detail
        : n.target.value;
    if (e.type === "checkbox")
      if (Array.isArray(r)) {
        let i = null;
        return (
          t.includes("number")
            ? (i = Nn(n.target.value))
            : t.includes("boolean")
              ? (i = nn(n.target.value))
              : (i = n.target.value),
          n.target.checked
            ? r.includes(i)
              ? r
              : r.concat([i])
            : r.filter((o) => !Pu(o, i))
        );
      } else return n.target.checked;
    else {
      if (e.tagName.toLowerCase() === "select" && e.multiple)
        return t.includes("number")
          ? Array.from(n.target.selectedOptions).map((i) => {
              let o = i.value || i.text;
              return Nn(o);
            })
          : t.includes("boolean")
            ? Array.from(n.target.selectedOptions).map((i) => {
                let o = i.value || i.text;
                return nn(o);
              })
            : Array.from(n.target.selectedOptions).map(
                (i) => i.value || i.text,
              );
      {
        let i;
        return (
          e.type === "radio"
            ? n.target.checked
              ? (i = n.target.value)
              : (i = r)
            : (i = n.target.value),
          t.includes("number")
            ? Nn(i)
            : t.includes("boolean")
              ? nn(i)
              : t.includes("trim")
                ? i.trim()
                : i
        );
      }
    }
  });
}
function Nn(e) {
  let t = e ? parseFloat(e) : null;
  return ku(t) ? t : e;
}
function Pu(e, t) {
  return e == t;
}
function ku(e) {
  return !Array.isArray(e) && !isNaN(e);
}
function fi(e) {
  return (
    e !== null &&
    typeof e == "object" &&
    typeof e.get == "function" &&
    typeof e.set == "function"
  );
}
Q("cloak", (e) =>
  queueMicrotask(() => ee(() => e.removeAttribute(xt("cloak")))),
);
go(() => `[${xt("init")}]`);
Q(
  "init",
  Ke((e, { expression: t }, { evaluate: n }) =>
    typeof t == "string" ? !!t.trim() && n(t, {}, !1) : n(t, {}, !1),
  ),
);
Q("text", (e, { expression: t }, { effect: n, evaluateLater: r }) => {
  let i = r(t);
  n(() => {
    i((o) => {
      ee(() => {
        e.textContent = o;
      });
    });
  });
});
Q("html", (e, { expression: t }, { effect: n, evaluateLater: r }) => {
  let i = r(t);
  n(() => {
    i((o) => {
      ee(() => {
        (e.innerHTML = o),
          (e._x_ignoreSelf = !0),
          Ne(e),
          delete e._x_ignoreSelf;
      });
    });
  });
});
br(io(":", oo(xt("bind:"))));
var Go = (
  e,
  { value: t, modifiers: n, expression: r, original: i },
  { effect: o, cleanup: a },
) => {
  if (!t) {
    let c = {};
    Fc(c),
      ae(e, r)(
        (l) => {
          Ro(e, l, i);
        },
        { scope: c },
      );
    return;
  }
  if (t === "key") return Bu(e, r);
  if (
    e._x_inlineBindings &&
    e._x_inlineBindings[t] &&
    e._x_inlineBindings[t].extract
  )
    return;
  let s = ae(e, r);
  o(() =>
    s((c) => {
      c === void 0 && typeof r == "string" && r.match(/\./) && (c = ""),
        ee(() => Eo(e, t, c, n));
    }),
  ),
    a(() => {
      e._x_undoAddedClasses && e._x_undoAddedClasses(),
        e._x_undoAddedStyles && e._x_undoAddedStyles();
    });
};
Go.inline = (e, { value: t, modifiers: n, expression: r }) => {
  t &&
    (e._x_inlineBindings || (e._x_inlineBindings = {}),
    (e._x_inlineBindings[t] = { expression: r, extract: !1 }));
};
Q("bind", Go);
function Bu(e, t) {
  e._x_keyExpression = t;
}
ho(() => `[${xt("data")}]`);
Q("data", (e, { expression: t }, { cleanup: n }) => {
  if ($u(e)) return;
  t = t === "" ? "{}" : t;
  let r = {};
  Kn(r, e);
  let i = {};
  Wc(i, r);
  let o = et(e, t, { scope: i });
  (o === void 0 || o === !0) && (o = {}), Kn(o, e);
  let a = yt(o);
  Xi(a);
  let s = Ht(e, a);
  a.init && et(e, a.init),
    n(() => {
      a.destroy && et(e, a.destroy), s();
    });
});
gn((e, t) => {
  e._x_dataStack &&
    ((t._x_dataStack = e._x_dataStack),
    t.setAttribute("data-has-alpine-state", !0));
});
function $u(e) {
  return Fe ? (Jn ? !0 : e.hasAttribute("data-has-alpine-state")) : !1;
}
Q("show", (e, { modifiers: t, expression: n }, { effect: r }) => {
  let i = ae(e, n);
  e._x_doHide ||
    (e._x_doHide = () => {
      ee(() => {
        e.style.setProperty(
          "display",
          "none",
          t.includes("important") ? "important" : void 0,
        );
      });
    }),
    e._x_doShow ||
      (e._x_doShow = () => {
        ee(() => {
          e.style.length === 1 && e.style.display === "none"
            ? e.removeAttribute("style")
            : e.style.removeProperty("display");
        });
      });
  let o = () => {
      e._x_doHide(), (e._x_isShown = !1);
    },
    a = () => {
      e._x_doShow(), (e._x_isShown = !0);
    },
    s = () => setTimeout(a),
    c = Xn(
      (v) => (v ? a() : o()),
      (v) => {
        typeof e._x_toggleAndCascadeWithTransitions == "function"
          ? e._x_toggleAndCascadeWithTransitions(e, v, a, o)
          : v
            ? s()
            : o();
      },
    ),
    f,
    l = !0;
  r(() =>
    i((v) => {
      (!l && v === f) ||
        (t.includes("immediate") && (v ? s() : o()), c(v), (f = v), (l = !1));
    }),
  );
});
Q("for", (e, { expression: t }, { effect: n, cleanup: r }) => {
  let i = Hu(t),
    o = ae(e, i.items),
    a = ae(e, e._x_keyExpression || "index");
  (e._x_prevKeys = []),
    (e._x_lookup = {}),
    n(() => ju(e, i, o, a)),
    r(() => {
      Object.values(e._x_lookup).forEach((s) => s.remove()),
        delete e._x_prevKeys,
        delete e._x_lookup;
    });
});
function ju(e, t, n, r) {
  let i = (a) => typeof a == "object" && !Array.isArray(a),
    o = e;
  n((a) => {
    Fu(a) && a >= 0 && (a = Array.from(Array(a).keys(), (h) => h + 1)),
      a === void 0 && (a = []);
    let s = e._x_lookup,
      c = e._x_prevKeys,
      f = [],
      l = [];
    if (i(a))
      a = Object.entries(a).map(([h, E]) => {
        let O = di(t, E, h, a);
        r(
          (M) => {
            l.includes(M) && ve("Duplicate key on x-for", e), l.push(M);
          },
          { scope: { index: h, ...O } },
        ),
          f.push(O);
      });
    else
      for (let h = 0; h < a.length; h++) {
        let E = di(t, a[h], h, a);
        r(
          (O) => {
            l.includes(O) && ve("Duplicate key on x-for", e), l.push(O);
          },
          { scope: { index: h, ...E } },
        ),
          f.push(E);
      }
    let v = [],
      b = [],
      _ = [],
      A = [];
    for (let h = 0; h < c.length; h++) {
      let E = c[h];
      l.indexOf(E) === -1 && _.push(E);
    }
    c = c.filter((h) => !_.includes(h));
    let S = "template";
    for (let h = 0; h < l.length; h++) {
      let E = l[h],
        O = c.indexOf(E);
      if (O === -1) c.splice(h, 0, E), v.push([S, h]);
      else if (O !== h) {
        let M = c.splice(h, 1)[0],
          u = c.splice(O - 1, 1)[0];
        c.splice(h, 0, u), c.splice(O, 0, M), b.push([M, u]);
      } else A.push(E);
      S = E;
    }
    for (let h = 0; h < _.length; h++) {
      let E = _[h];
      s[E]._x_effects && s[E]._x_effects.forEach($i),
        s[E].remove(),
        (s[E] = null),
        delete s[E];
    }
    for (let h = 0; h < b.length; h++) {
      let [E, O] = b[h],
        M = s[E],
        u = s[O],
        I = document.createElement("div");
      ee(() => {
        u || ve('x-for ":key" is undefined or invalid', o, O, s),
          u.after(I),
          M.after(u),
          u._x_currentIfEl && u.after(u._x_currentIfEl),
          I.before(M),
          M._x_currentIfEl && M.after(M._x_currentIfEl),
          I.remove();
      }),
        u._x_refreshXForScope(f[l.indexOf(O)]);
    }
    for (let h = 0; h < v.length; h++) {
      let [E, O] = v[h],
        M = E === "template" ? o : s[E];
      M._x_currentIfEl && (M = M._x_currentIfEl);
      let u = f[O],
        I = l[O],
        m = document.importNode(o.content, !0).firstElementChild,
        L = yt(u);
      Ht(m, L, o),
        (m._x_refreshXForScope = (K) => {
          Object.entries(K).forEach(([P, H]) => {
            L[P] = H;
          });
        }),
        ee(() => {
          M.after(m), Ke(() => Ne(m))();
        }),
        typeof I == "object" &&
          ve(
            "x-for key cannot be an object, it must be a string or an integer",
            o,
          ),
        (s[I] = m);
    }
    for (let h = 0; h < A.length; h++)
      s[A[h]]._x_refreshXForScope(f[l.indexOf(A[h])]);
    o._x_prevKeys = l;
  });
}
function Hu(e) {
  let t = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/,
    n = /^\s*\(|\)\s*$/g,
    r = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,
    i = e.match(r);
  if (!i) return;
  let o = {};
  o.items = i[2].trim();
  let a = i[1].replace(n, "").trim(),
    s = a.match(t);
  return (
    s
      ? ((o.item = a.replace(t, "").trim()),
        (o.index = s[1].trim()),
        s[2] && (o.collection = s[2].trim()))
      : (o.item = a),
    o
  );
}
function di(e, t, n, r) {
  let i = {};
  return (
    /^\[.*\]$/.test(e.item) && Array.isArray(t)
      ? e.item
          .replace("[", "")
          .replace("]", "")
          .split(",")
          .map((a) => a.trim())
          .forEach((a, s) => {
            i[a] = t[s];
          })
      : /^\{.*\}$/.test(e.item) && !Array.isArray(t) && typeof t == "object"
        ? e.item
            .replace("{", "")
            .replace("}", "")
            .split(",")
            .map((a) => a.trim())
            .forEach((a) => {
              i[a] = t[a];
            })
        : (i[e.item] = t),
    e.index && (i[e.index] = n),
    e.collection && (i[e.collection] = r),
    i
  );
}
function Fu(e) {
  return !Array.isArray(e) && !isNaN(e);
}
function Xo() {}
Xo.inline = (e, { expression: t }, { cleanup: n }) => {
  let r = pn(e);
  r._x_refs || (r._x_refs = {}),
    (r._x_refs[t] = e),
    n(() => delete r._x_refs[t]);
};
Q("ref", Xo);
Q("if", (e, { expression: t }, { effect: n, cleanup: r }) => {
  e.tagName.toLowerCase() !== "template" &&
    ve("x-if can only be used on a <template> tag", e);
  let i = ae(e, t),
    o = () => {
      if (e._x_currentIfEl) return e._x_currentIfEl;
      let s = e.content.cloneNode(!0).firstElementChild;
      return (
        Ht(s, {}, e),
        ee(() => {
          e.after(s), Ke(() => Ne(s))();
        }),
        (e._x_currentIfEl = s),
        (e._x_undoIf = () => {
          He(s, (c) => {
            c._x_effects && c._x_effects.forEach($i);
          }),
            s.remove(),
            delete e._x_currentIfEl;
        }),
        s
      );
    },
    a = () => {
      e._x_undoIf && (e._x_undoIf(), delete e._x_undoIf);
    };
  n(() =>
    i((s) => {
      s ? o() : a();
    }),
  ),
    r(() => e._x_undoIf && e._x_undoIf());
});
Q("id", (e, { expression: t }, { evaluate: n }) => {
  n(t).forEach((i) => Tu(e, i));
});
gn((e, t) => {
  e._x_ids && (t._x_ids = e._x_ids);
});
br(io("@", oo(xt("on:"))));
Q(
  "on",
  Ke((e, { value: t, modifiers: n, expression: r }, { cleanup: i }) => {
    let o = r ? ae(e, r) : () => {};
    e.tagName.toLowerCase() === "template" &&
      (e._x_forwardEvents || (e._x_forwardEvents = []),
      e._x_forwardEvents.includes(t) || e._x_forwardEvents.push(t));
    let a = tr(e, t, n, (s) => {
      o(() => {}, { scope: { $event: s }, params: [s] });
    });
    i(() => a());
  }),
);
yn("Collapse", "collapse", "collapse");
yn("Intersect", "intersect", "intersect");
yn("Focus", "trap", "focus");
yn("Mask", "mask", "mask");
function yn(e, t, n) {
  Q(t, (r) =>
    ve(
      `You can't use [x-${t}] without first installing the "${e}" plugin here: https://alpinejs.dev/plugins/${n}`,
      r,
    ),
  );
}
Wt.setEvaluator(eo);
Wt.setReactivityEngine({ reactive: Cr, effect: Qc, release: eu, raw: X });
var Uu = Wt,
  Wu = Uu;
function Ku(e) {
  return e && e.__esModule && Object.prototype.hasOwnProperty.call(e, "default")
    ? e.default
    : e;
}
function Yo(e) {
  return (
    e instanceof Map
      ? (e.clear =
          e.delete =
          e.set =
            function () {
              throw new Error("map is read-only");
            })
      : e instanceof Set &&
        (e.add =
          e.clear =
          e.delete =
            function () {
              throw new Error("set is read-only");
            }),
    Object.freeze(e),
    Object.getOwnPropertyNames(e).forEach((t) => {
      const n = e[t],
        r = typeof n;
      (r === "object" || r === "function") && !Object.isFrozen(n) && Yo(n);
    }),
    e
  );
}
class pi {
  constructor(t) {
    t.data === void 0 && (t.data = {}),
      (this.data = t.data),
      (this.isMatchIgnored = !1);
  }
  ignoreMatch() {
    this.isMatchIgnored = !0;
  }
}
function Jo(e) {
  return e
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#x27;");
}
function je(e, ...t) {
  const n = Object.create(null);
  for (const r in e) n[r] = e[r];
  return (
    t.forEach(function (r) {
      for (const i in r) n[i] = r[i];
    }),
    n
  );
}
const zu = "</span>",
  hi = (e) => !!e.scope,
  Vu = (e, { prefix: t }) => {
    if (e.startsWith("language:")) return e.replace("language:", "language-");
    if (e.includes(".")) {
      const n = e.split(".");
      return [
        `${t}${n.shift()}`,
        ...n.map((r, i) => `${r}${"_".repeat(i + 1)}`),
      ].join(" ");
    }
    return `${t}${e}`;
  };
class qu {
  constructor(t, n) {
    (this.buffer = ""), (this.classPrefix = n.classPrefix), t.walk(this);
  }
  addText(t) {
    this.buffer += Jo(t);
  }
  openNode(t) {
    if (!hi(t)) return;
    const n = Vu(t.scope, { prefix: this.classPrefix });
    this.span(n);
  }
  closeNode(t) {
    hi(t) && (this.buffer += zu);
  }
  value() {
    return this.buffer;
  }
  span(t) {
    this.buffer += `<span class="${t}">`;
  }
}
const gi = (e = {}) => {
  const t = { children: [] };
  return Object.assign(t, e), t;
};
class Rr {
  constructor() {
    (this.rootNode = gi()), (this.stack = [this.rootNode]);
  }
  get top() {
    return this.stack[this.stack.length - 1];
  }
  get root() {
    return this.rootNode;
  }
  add(t) {
    this.top.children.push(t);
  }
  openNode(t) {
    const n = gi({ scope: t });
    this.add(n), this.stack.push(n);
  }
  closeNode() {
    if (this.stack.length > 1) return this.stack.pop();
  }
  closeAllNodes() {
    for (; this.closeNode(); );
  }
  toJSON() {
    return JSON.stringify(this.rootNode, null, 4);
  }
  walk(t) {
    return this.constructor._walk(t, this.rootNode);
  }
  static _walk(t, n) {
    return (
      typeof n == "string"
        ? t.addText(n)
        : n.children &&
          (t.openNode(n),
          n.children.forEach((r) => this._walk(t, r)),
          t.closeNode(n)),
      t
    );
  }
  static _collapse(t) {
    typeof t != "string" &&
      t.children &&
      (t.children.every((n) => typeof n == "string")
        ? (t.children = [t.children.join("")])
        : t.children.forEach((n) => {
            Rr._collapse(n);
          }));
  }
}
class Gu extends Rr {
  constructor(t) {
    super(), (this.options = t);
  }
  addText(t) {
    t !== "" && this.add(t);
  }
  startScope(t) {
    this.openNode(t);
  }
  endScope() {
    this.closeNode();
  }
  __addSublanguage(t, n) {
    const r = t.root;
    n && (r.scope = `language:${n}`), this.add(r);
  }
  toHTML() {
    return new qu(this, this.options).value();
  }
  finalize() {
    return this.closeAllNodes(), !0;
  }
}
function Pt(e) {
  return e ? (typeof e == "string" ? e : e.source) : null;
}
function Zo(e) {
  return at("(?=", e, ")");
}
function Xu(e) {
  return at("(?:", e, ")*");
}
function Yu(e) {
  return at("(?:", e, ")?");
}
function at(...e) {
  return e.map((n) => Pt(n)).join("");
}
function Ju(e) {
  const t = e[e.length - 1];
  return typeof t == "object" && t.constructor === Object
    ? (e.splice(e.length - 1, 1), t)
    : {};
}
function Ir(...e) {
  return (
    "(" + (Ju(e).capture ? "" : "?:") + e.map((r) => Pt(r)).join("|") + ")"
  );
}
function Qo(e) {
  return new RegExp(e.toString() + "|").exec("").length - 1;
}
function Zu(e, t) {
  const n = e && e.exec(t);
  return n && n.index === 0;
}
const Qu = /\[(?:[^\\\]]|\\.)*\]|\(\??|\\([1-9][0-9]*)|\\./;
function Dr(e, { joinWith: t }) {
  let n = 0;
  return e
    .map((r) => {
      n += 1;
      const i = n;
      let o = Pt(r),
        a = "";
      for (; o.length > 0; ) {
        const s = Qu.exec(o);
        if (!s) {
          a += o;
          break;
        }
        (a += o.substring(0, s.index)),
          (o = o.substring(s.index + s[0].length)),
          s[0][0] === "\\" && s[1]
            ? (a += "\\" + String(Number(s[1]) + i))
            : ((a += s[0]), s[0] === "(" && n++);
      }
      return a;
    })
    .map((r) => `(${r})`)
    .join(t);
}
const el = /\b\B/,
  ea = "[a-zA-Z]\\w*",
  Nr = "[a-zA-Z_]\\w*",
  ta = "\\b\\d+(\\.\\d+)?",
  na = "(-?)(\\b0[xX][a-fA-F0-9]+|(\\b\\d+(\\.\\d*)?|\\.\\d+)([eE][-+]?\\d+)?)",
  ra = "\\b(0b[01]+)",
  tl =
    "!|!=|!==|%|%=|&|&&|&=|\\*|\\*=|\\+|\\+=|,|-|-=|/=|/|:|;|<<|<<=|<=|<|===|==|=|>>>=|>>=|>=|>>>|>>|>|\\?|\\[|\\{|\\(|\\^|\\^=|\\||\\|=|\\|\\||~",
  nl = (e = {}) => {
    const t = /^#![ ]*\//;
    return (
      e.binary && (e.begin = at(t, /.*\b/, e.binary, /\b.*/)),
      je(
        {
          scope: "meta",
          begin: t,
          end: /$/,
          relevance: 0,
          "on:begin": (n, r) => {
            n.index !== 0 && r.ignoreMatch();
          },
        },
        e,
      )
    );
  },
  kt = { begin: "\\\\[\\s\\S]", relevance: 0 },
  rl = {
    scope: "string",
    begin: "'",
    end: "'",
    illegal: "\\n",
    contains: [kt],
  },
  il = {
    scope: "string",
    begin: '"',
    end: '"',
    illegal: "\\n",
    contains: [kt],
  },
  ol = {
    begin:
      /\b(a|an|the|are|I'm|isn't|don't|doesn't|won't|but|just|should|pretty|simply|enough|gonna|going|wtf|so|such|will|you|your|they|like|more)\b/,
  },
  mn = function (e, t, n = {}) {
    const r = je({ scope: "comment", begin: e, end: t, contains: [] }, n);
    r.contains.push({
      scope: "doctag",
      begin: "[ ]*(?=(TODO|FIXME|NOTE|BUG|OPTIMIZE|HACK|XXX):)",
      end: /(TODO|FIXME|NOTE|BUG|OPTIMIZE|HACK|XXX):/,
      excludeBegin: !0,
      relevance: 0,
    });
    const i = Ir(
      "I",
      "a",
      "is",
      "so",
      "us",
      "to",
      "at",
      "if",
      "in",
      "it",
      "on",
      /[A-Za-z]+['](d|ve|re|ll|t|s|n)/,
      /[A-Za-z]+[-][a-z]+/,
      /[A-Za-z][a-z]{2,}/,
    );
    return (
      r.contains.push({
        begin: at(/[ ]+/, "(", i, /[.]?[:]?([.][ ]|[ ])/, "){3}"),
      }),
      r
    );
  },
  al = mn("//", "$"),
  sl = mn("/\\*", "\\*/"),
  cl = mn("#", "$"),
  ul = { scope: "number", begin: ta, relevance: 0 },
  ll = { scope: "number", begin: na, relevance: 0 },
  fl = { scope: "number", begin: ra, relevance: 0 },
  dl = {
    scope: "regexp",
    begin: /\/(?=[^/\n]*\/)/,
    end: /\/[gimuy]*/,
    contains: [kt, { begin: /\[/, end: /\]/, relevance: 0, contains: [kt] }],
  },
  pl = { scope: "title", begin: ea, relevance: 0 },
  hl = { scope: "title", begin: Nr, relevance: 0 },
  gl = { begin: "\\.\\s*" + Nr, relevance: 0 },
  vl = function (e) {
    return Object.assign(e, {
      "on:begin": (t, n) => {
        n.data._beginMatch = t[1];
      },
      "on:end": (t, n) => {
        n.data._beginMatch !== t[1] && n.ignoreMatch();
      },
    });
  };
var Qt = Object.freeze({
  __proto__: null,
  APOS_STRING_MODE: rl,
  BACKSLASH_ESCAPE: kt,
  BINARY_NUMBER_MODE: fl,
  BINARY_NUMBER_RE: ra,
  COMMENT: mn,
  C_BLOCK_COMMENT_MODE: sl,
  C_LINE_COMMENT_MODE: al,
  C_NUMBER_MODE: ll,
  C_NUMBER_RE: na,
  END_SAME_AS_BEGIN: vl,
  HASH_COMMENT_MODE: cl,
  IDENT_RE: ea,
  MATCH_NOTHING_RE: el,
  METHOD_GUARD: gl,
  NUMBER_MODE: ul,
  NUMBER_RE: ta,
  PHRASAL_WORDS_MODE: ol,
  QUOTE_STRING_MODE: il,
  REGEXP_MODE: dl,
  RE_STARTERS_RE: tl,
  SHEBANG: nl,
  TITLE_MODE: pl,
  UNDERSCORE_IDENT_RE: Nr,
  UNDERSCORE_TITLE_MODE: hl,
});
function _l(e, t) {
  e.input[e.index - 1] === "." && t.ignoreMatch();
}
function bl(e, t) {
  e.className !== void 0 && ((e.scope = e.className), delete e.className);
}
function yl(e, t) {
  t &&
    e.beginKeywords &&
    ((e.begin =
      "\\b(" + e.beginKeywords.split(" ").join("|") + ")(?!\\.)(?=\\b|\\s)"),
    (e.__beforeBegin = _l),
    (e.keywords = e.keywords || e.beginKeywords),
    delete e.beginKeywords,
    e.relevance === void 0 && (e.relevance = 0));
}
function ml(e, t) {
  Array.isArray(e.illegal) && (e.illegal = Ir(...e.illegal));
}
function xl(e, t) {
  if (e.match) {
    if (e.begin || e.end)
      throw new Error("begin & end are not supported with match");
    (e.begin = e.match), delete e.match;
  }
}
function El(e, t) {
  e.relevance === void 0 && (e.relevance = 1);
}
const wl = (e, t) => {
    if (!e.beforeMatch) return;
    if (e.starts) throw new Error("beforeMatch cannot be used with starts");
    const n = Object.assign({}, e);
    Object.keys(e).forEach((r) => {
      delete e[r];
    }),
      (e.keywords = n.keywords),
      (e.begin = at(n.beforeMatch, Zo(n.begin))),
      (e.starts = {
        relevance: 0,
        contains: [Object.assign(n, { endsParent: !0 })],
      }),
      (e.relevance = 0),
      delete n.beforeMatch;
  },
  Ol = [
    "of",
    "and",
    "for",
    "in",
    "not",
    "or",
    "if",
    "then",
    "parent",
    "list",
    "value",
  ],
  Al = "keyword";
function ia(e, t, n = Al) {
  const r = Object.create(null);
  return (
    typeof e == "string"
      ? i(n, e.split(" "))
      : Array.isArray(e)
        ? i(n, e)
        : Object.keys(e).forEach(function (o) {
            Object.assign(r, ia(e[o], t, o));
          }),
    r
  );
  function i(o, a) {
    t && (a = a.map((s) => s.toLowerCase())),
      a.forEach(function (s) {
        const c = s.split("|");
        r[c[0]] = [o, Sl(c[0], c[1])];
      });
  }
}
function Sl(e, t) {
  return t ? Number(t) : Tl(e) ? 0 : 1;
}
function Tl(e) {
  return Ol.includes(e.toLowerCase());
}
const vi = {},
  rt = (e) => {
    console.error(e);
  },
  _i = (e, ...t) => {
    console.log(`WARN: ${e}`, ...t);
  },
  lt = (e, t) => {
    vi[`${e}/${t}`] ||
      (console.log(`Deprecated as of ${e}. ${t}`), (vi[`${e}/${t}`] = !0));
  },
  ln = new Error();
function oa(e, t, { key: n }) {
  let r = 0;
  const i = e[n],
    o = {},
    a = {};
  for (let s = 1; s <= t.length; s++)
    (a[s + r] = i[s]), (o[s + r] = !0), (r += Qo(t[s - 1]));
  (e[n] = a), (e[n]._emit = o), (e[n]._multi = !0);
}
function Ml(e) {
  if (Array.isArray(e.begin)) {
    if (e.skip || e.excludeBegin || e.returnBegin)
      throw (
        (rt(
          "skip, excludeBegin, returnBegin not compatible with beginScope: {}",
        ),
        ln)
      );
    if (typeof e.beginScope != "object" || e.beginScope === null)
      throw (rt("beginScope must be object"), ln);
    oa(e, e.begin, { key: "beginScope" }),
      (e.begin = Dr(e.begin, { joinWith: "" }));
  }
}
function Cl(e) {
  if (Array.isArray(e.end)) {
    if (e.skip || e.excludeEnd || e.returnEnd)
      throw (
        (rt("skip, excludeEnd, returnEnd not compatible with endScope: {}"), ln)
      );
    if (typeof e.endScope != "object" || e.endScope === null)
      throw (rt("endScope must be object"), ln);
    oa(e, e.end, { key: "endScope" }), (e.end = Dr(e.end, { joinWith: "" }));
  }
}
function Rl(e) {
  e.scope &&
    typeof e.scope == "object" &&
    e.scope !== null &&
    ((e.beginScope = e.scope), delete e.scope);
}
function Il(e) {
  Rl(e),
    typeof e.beginScope == "string" && (e.beginScope = { _wrap: e.beginScope }),
    typeof e.endScope == "string" && (e.endScope = { _wrap: e.endScope }),
    Ml(e),
    Cl(e);
}
function Dl(e) {
  function t(a, s) {
    return new RegExp(
      Pt(a),
      "m" +
        (e.case_insensitive ? "i" : "") +
        (e.unicodeRegex ? "u" : "") +
        (s ? "g" : ""),
    );
  }
  class n {
    constructor() {
      (this.matchIndexes = {}),
        (this.regexes = []),
        (this.matchAt = 1),
        (this.position = 0);
    }
    addRule(s, c) {
      (c.position = this.position++),
        (this.matchIndexes[this.matchAt] = c),
        this.regexes.push([c, s]),
        (this.matchAt += Qo(s) + 1);
    }
    compile() {
      this.regexes.length === 0 && (this.exec = () => null);
      const s = this.regexes.map((c) => c[1]);
      (this.matcherRe = t(Dr(s, { joinWith: "|" }), !0)), (this.lastIndex = 0);
    }
    exec(s) {
      this.matcherRe.lastIndex = this.lastIndex;
      const c = this.matcherRe.exec(s);
      if (!c) return null;
      const f = c.findIndex((v, b) => b > 0 && v !== void 0),
        l = this.matchIndexes[f];
      return c.splice(0, f), Object.assign(c, l);
    }
  }
  class r {
    constructor() {
      (this.rules = []),
        (this.multiRegexes = []),
        (this.count = 0),
        (this.lastIndex = 0),
        (this.regexIndex = 0);
    }
    getMatcher(s) {
      if (this.multiRegexes[s]) return this.multiRegexes[s];
      const c = new n();
      return (
        this.rules.slice(s).forEach(([f, l]) => c.addRule(f, l)),
        c.compile(),
        (this.multiRegexes[s] = c),
        c
      );
    }
    resumingScanAtSamePosition() {
      return this.regexIndex !== 0;
    }
    considerAll() {
      this.regexIndex = 0;
    }
    addRule(s, c) {
      this.rules.push([s, c]), c.type === "begin" && this.count++;
    }
    exec(s) {
      const c = this.getMatcher(this.regexIndex);
      c.lastIndex = this.lastIndex;
      let f = c.exec(s);
      if (
        this.resumingScanAtSamePosition() &&
        !(f && f.index === this.lastIndex)
      ) {
        const l = this.getMatcher(0);
        (l.lastIndex = this.lastIndex + 1), (f = l.exec(s));
      }
      return (
        f &&
          ((this.regexIndex += f.position + 1),
          this.regexIndex === this.count && this.considerAll()),
        f
      );
    }
  }
  function i(a) {
    const s = new r();
    return (
      a.contains.forEach((c) => s.addRule(c.begin, { rule: c, type: "begin" })),
      a.terminatorEnd && s.addRule(a.terminatorEnd, { type: "end" }),
      a.illegal && s.addRule(a.illegal, { type: "illegal" }),
      s
    );
  }
  function o(a, s) {
    const c = a;
    if (a.isCompiled) return c;
    [bl, xl, Il, wl].forEach((l) => l(a, s)),
      e.compilerExtensions.forEach((l) => l(a, s)),
      (a.__beforeBegin = null),
      [yl, ml, El].forEach((l) => l(a, s)),
      (a.isCompiled = !0);
    let f = null;
    return (
      typeof a.keywords == "object" &&
        a.keywords.$pattern &&
        ((a.keywords = Object.assign({}, a.keywords)),
        (f = a.keywords.$pattern),
        delete a.keywords.$pattern),
      (f = f || /\w+/),
      a.keywords && (a.keywords = ia(a.keywords, e.case_insensitive)),
      (c.keywordPatternRe = t(f, !0)),
      s &&
        (a.begin || (a.begin = /\B|\b/),
        (c.beginRe = t(c.begin)),
        !a.end && !a.endsWithParent && (a.end = /\B|\b/),
        a.end && (c.endRe = t(c.end)),
        (c.terminatorEnd = Pt(c.end) || ""),
        a.endsWithParent &&
          s.terminatorEnd &&
          (c.terminatorEnd += (a.end ? "|" : "") + s.terminatorEnd)),
      a.illegal && (c.illegalRe = t(a.illegal)),
      a.contains || (a.contains = []),
      (a.contains = [].concat(
        ...a.contains.map(function (l) {
          return Nl(l === "self" ? a : l);
        }),
      )),
      a.contains.forEach(function (l) {
        o(l, c);
      }),
      a.starts && o(a.starts, s),
      (c.matcher = i(c)),
      c
    );
  }
  if (
    (e.compilerExtensions || (e.compilerExtensions = []),
    e.contains && e.contains.includes("self"))
  )
    throw new Error(
      "ERR: contains `self` is not supported at the top-level of a language.  See documentation.",
    );
  return (e.classNameAliases = je(e.classNameAliases || {})), o(e);
}
function aa(e) {
  return e ? e.endsWithParent || aa(e.starts) : !1;
}
function Nl(e) {
  return (
    e.variants &&
      !e.cachedVariants &&
      (e.cachedVariants = e.variants.map(function (t) {
        return je(e, { variants: null }, t);
      })),
    e.cachedVariants
      ? e.cachedVariants
      : aa(e)
        ? je(e, { starts: e.starts ? je(e.starts) : null })
        : Object.isFrozen(e)
          ? je(e)
          : e
  );
}
var Ll = "11.9.0";
class Pl extends Error {
  constructor(t, n) {
    super(t), (this.name = "HTMLInjectionError"), (this.html = n);
  }
}
const Ln = Jo,
  bi = je,
  yi = Symbol("nomatch"),
  kl = 7,
  sa = function (e) {
    const t = Object.create(null),
      n = Object.create(null),
      r = [];
    let i = !0;
    const o =
        "Could not find the language '{}', did you forget to load/include a language module?",
      a = { disableAutodetect: !0, name: "Plain text", contains: [] };
    let s = {
      ignoreUnescapedHTML: !1,
      throwUnescapedHTML: !1,
      noHighlightRe: /^(no-?highlight)$/i,
      languageDetectRe: /\blang(?:uage)?-([\w-]+)\b/i,
      classPrefix: "hljs-",
      cssSelector: "pre code",
      languages: null,
      __emitter: Gu,
    };
    function c(p) {
      return s.noHighlightRe.test(p);
    }
    function f(p) {
      let y = p.className + " ";
      y += p.parentNode ? p.parentNode.className : "";
      const C = s.languageDetectRe.exec(y);
      if (C) {
        const N = P(C[1]);
        return (
          N ||
            (_i(o.replace("{}", C[1])),
            _i("Falling back to no-highlight mode for this block.", p)),
          N ? C[1] : "no-highlight"
        );
      }
      return y.split(/\s+/).find((N) => c(N) || P(N));
    }
    function l(p, y, C) {
      let N = "",
        W = "";
      typeof y == "object"
        ? ((N = p), (C = y.ignoreIllegals), (W = y.language))
        : (lt("10.7.0", "highlight(lang, code, ...args) has been deprecated."),
          lt(
            "10.7.0",
            `Please use highlight(code, options) instead.
https://github.com/highlightjs/highlight.js/issues/2277`,
          ),
          (W = p),
          (N = y)),
        C === void 0 && (C = !0);
      const J = { code: N, language: W };
      Y("before:highlight", J);
      const re = J.result ? J.result : v(J.language, J.code, C);
      return (re.code = J.code), Y("after:highlight", re), re;
    }
    function v(p, y, C, N) {
      const W = Object.create(null);
      function J(g, x) {
        return g.keywords[x];
      }
      function re() {
        if (!T.keywords) {
          V.addText($);
          return;
        }
        let g = 0;
        T.keywordPatternRe.lastIndex = 0;
        let x = T.keywordPatternRe.exec($),
          D = "";
        for (; x; ) {
          D += $.substring(g, x.index);
          const j = ie.case_insensitive ? x[0].toLowerCase() : x[0],
            ne = J(T, j);
          if (ne) {
            const [me, En] = ne;
            if (
              (V.addText(D),
              (D = ""),
              (W[j] = (W[j] || 0) + 1),
              W[j] <= kl && (he += En),
              me.startsWith("_"))
            )
              D += x[0];
            else {
              const d = ie.classNameAliases[me] || me;
              Z(x[0], d);
            }
          } else D += x[0];
          (g = T.keywordPatternRe.lastIndex), (x = T.keywordPatternRe.exec($));
        }
        (D += $.substring(g)), V.addText(D);
      }
      function se() {
        if ($ === "") return;
        let g = null;
        if (typeof T.subLanguage == "string") {
          if (!t[T.subLanguage]) {
            V.addText($);
            return;
          }
          (g = v(T.subLanguage, $, !0, pe[T.subLanguage])),
            (pe[T.subLanguage] = g._top);
        } else g = _($, T.subLanguage.length ? T.subLanguage : null);
        T.relevance > 0 && (he += g.relevance),
          V.__addSublanguage(g._emitter, g.language);
      }
      function G() {
        T.subLanguage != null ? se() : re(), ($ = "");
      }
      function Z(g, x) {
        g !== "" && (V.startScope(x), V.addText(g), V.endScope());
      }
      function Ce(g, x) {
        let D = 1;
        const j = x.length - 1;
        for (; D <= j; ) {
          if (!g._emit[D]) {
            D++;
            continue;
          }
          const ne = ie.classNameAliases[g[D]] || g[D],
            me = x[D];
          ne ? Z(me, ne) : (($ = me), re(), ($ = "")), D++;
        }
      }
      function de(g, x) {
        return (
          g.scope &&
            typeof g.scope == "string" &&
            V.openNode(ie.classNameAliases[g.scope] || g.scope),
          g.beginScope &&
            (g.beginScope._wrap
              ? (Z(
                  $,
                  ie.classNameAliases[g.beginScope._wrap] || g.beginScope._wrap,
                ),
                ($ = ""))
              : g.beginScope._multi && (Ce(g.beginScope, x), ($ = ""))),
          (T = Object.create(g, { parent: { value: T } })),
          T
        );
      }
      function Re(g, x, D) {
        let j = Zu(g.endRe, D);
        if (j) {
          if (g["on:end"]) {
            const ne = new pi(g);
            g["on:end"](x, ne), ne.isMatchIgnored && (j = !1);
          }
          if (j) {
            for (; g.endsParent && g.parent; ) g = g.parent;
            return g;
          }
        }
        if (g.endsWithParent) return Re(g.parent, x, D);
      }
      function Oe(g) {
        return T.matcher.regexIndex === 0 ? (($ += g[0]), 1) : ((Be = !0), 0);
      }
      function Le(g) {
        const x = g[0],
          D = g.rule,
          j = new pi(D),
          ne = [D.__beforeBegin, D["on:begin"]];
        for (const me of ne)
          if (me && (me(g, j), j.isMatchIgnored)) return Oe(x);
        return (
          D.skip
            ? ($ += x)
            : (D.excludeBegin && ($ += x),
              G(),
              !D.returnBegin && !D.excludeBegin && ($ = x)),
          de(D, g),
          D.returnBegin ? 0 : x.length
        );
      }
      function st(g) {
        const x = g[0],
          D = y.substring(g.index),
          j = Re(T, g, D);
        if (!j) return yi;
        const ne = T;
        T.endScope && T.endScope._wrap
          ? (G(), Z(x, T.endScope._wrap))
          : T.endScope && T.endScope._multi
            ? (G(), Ce(T.endScope, g))
            : ne.skip
              ? ($ += x)
              : (ne.returnEnd || ne.excludeEnd || ($ += x),
                G(),
                ne.excludeEnd && ($ = x));
        do
          T.scope && V.closeNode(),
            !T.skip && !T.subLanguage && (he += T.relevance),
            (T = T.parent);
        while (T !== j.parent);
        return j.starts && de(j.starts, g), ne.returnEnd ? 0 : x.length;
      }
      function ze() {
        const g = [];
        for (let x = T; x !== ie; x = x.parent) x.scope && g.unshift(x.scope);
        g.forEach((x) => V.openNode(x));
      }
      let Ie = {};
      function Ve(g, x) {
        const D = x && x[0];
        if ((($ += g), D == null)) return G(), 0;
        if (
          Ie.type === "begin" &&
          x.type === "end" &&
          Ie.index === x.index &&
          D === ""
        ) {
          if ((($ += y.slice(x.index, x.index + 1)), !i)) {
            const j = new Error(`0 width match regex (${p})`);
            throw ((j.languageName = p), (j.badRule = Ie.rule), j);
          }
          return 1;
        }
        if (((Ie = x), x.type === "begin")) return Le(x);
        if (x.type === "illegal" && !C) {
          const j = new Error(
            'Illegal lexeme "' +
              D +
              '" for mode "' +
              (T.scope || "<unnamed>") +
              '"',
          );
          throw ((j.mode = T), j);
        } else if (x.type === "end") {
          const j = st(x);
          if (j !== yi) return j;
        }
        if (x.type === "illegal" && D === "") return 1;
        if (ke > 1e5 && ke > x.index * 3)
          throw new Error(
            "potential infinite loop, way more iterations than matches",
          );
        return ($ += D), D.length;
      }
      const ie = P(p);
      if (!ie)
        throw (
          (rt(o.replace("{}", p)), new Error('Unknown language: "' + p + '"'))
        );
      const qe = Dl(ie);
      let Pe = "",
        T = N || qe;
      const pe = {},
        V = new s.__emitter(s);
      ze();
      let $ = "",
        he = 0,
        le = 0,
        ke = 0,
        Be = !1;
      try {
        if (ie.__emitTokens) ie.__emitTokens(y, V);
        else {
          for (T.matcher.considerAll(); ; ) {
            ke++,
              Be ? (Be = !1) : T.matcher.considerAll(),
              (T.matcher.lastIndex = le);
            const g = T.matcher.exec(y);
            if (!g) break;
            const x = y.substring(le, g.index),
              D = Ve(x, g);
            le = g.index + D;
          }
          Ve(y.substring(le));
        }
        return (
          V.finalize(),
          (Pe = V.toHTML()),
          {
            language: p,
            value: Pe,
            relevance: he,
            illegal: !1,
            _emitter: V,
            _top: T,
          }
        );
      } catch (g) {
        if (g.message && g.message.includes("Illegal"))
          return {
            language: p,
            value: Ln(y),
            illegal: !0,
            relevance: 0,
            _illegalBy: {
              message: g.message,
              index: le,
              context: y.slice(le - 100, le + 100),
              mode: g.mode,
              resultSoFar: Pe,
            },
            _emitter: V,
          };
        if (i)
          return {
            language: p,
            value: Ln(y),
            illegal: !1,
            relevance: 0,
            errorRaised: g,
            _emitter: V,
            _top: T,
          };
        throw g;
      }
    }
    function b(p) {
      const y = {
        value: Ln(p),
        illegal: !1,
        relevance: 0,
        _top: a,
        _emitter: new s.__emitter(s),
      };
      return y._emitter.addText(p), y;
    }
    function _(p, y) {
      y = y || s.languages || Object.keys(t);
      const C = b(p),
        N = y
          .filter(P)
          .filter(q)
          .map((G) => v(G, p, !1));
      N.unshift(C);
      const W = N.sort((G, Z) => {
          if (G.relevance !== Z.relevance) return Z.relevance - G.relevance;
          if (G.language && Z.language) {
            if (P(G.language).supersetOf === Z.language) return 1;
            if (P(Z.language).supersetOf === G.language) return -1;
          }
          return 0;
        }),
        [J, re] = W,
        se = J;
      return (se.secondBest = re), se;
    }
    function A(p, y, C) {
      const N = (y && n[y]) || C;
      p.classList.add("hljs"), p.classList.add(`language-${N}`);
    }
    function S(p) {
      let y = null;
      const C = f(p);
      if (c(C)) return;
      if (
        (Y("before:highlightElement", { el: p, language: C }),
        p.dataset.highlighted)
      ) {
        console.log(
          "Element previously highlighted. To highlight again, first unset `dataset.highlighted`.",
          p,
        );
        return;
      }
      if (
        p.children.length > 0 &&
        (s.ignoreUnescapedHTML ||
          (console.warn(
            "One of your code blocks includes unescaped HTML. This is a potentially serious security risk.",
          ),
          console.warn(
            "https://github.com/highlightjs/highlight.js/wiki/security",
          ),
          console.warn("The element with unescaped HTML:"),
          console.warn(p)),
        s.throwUnescapedHTML)
      )
        throw new Pl(
          "One of your code blocks includes unescaped HTML.",
          p.innerHTML,
        );
      y = p;
      const N = y.textContent,
        W = C ? l(N, { language: C, ignoreIllegals: !0 }) : _(N);
      (p.innerHTML = W.value),
        (p.dataset.highlighted = "yes"),
        A(p, C, W.language),
        (p.result = {
          language: W.language,
          re: W.relevance,
          relevance: W.relevance,
        }),
        W.secondBest &&
          (p.secondBest = {
            language: W.secondBest.language,
            relevance: W.secondBest.relevance,
          }),
        Y("after:highlightElement", { el: p, result: W, text: N });
    }
    function h(p) {
      s = bi(s, p);
    }
    const E = () => {
      u(),
        lt("10.6.0", "initHighlighting() deprecated.  Use highlightAll() now.");
    };
    function O() {
      u(),
        lt(
          "10.6.0",
          "initHighlightingOnLoad() deprecated.  Use highlightAll() now.",
        );
    }
    let M = !1;
    function u() {
      if (document.readyState === "loading") {
        M = !0;
        return;
      }
      document.querySelectorAll(s.cssSelector).forEach(S);
    }
    function I() {
      M && u();
    }
    typeof window < "u" &&
      window.addEventListener &&
      window.addEventListener("DOMContentLoaded", I, !1);
    function m(p, y) {
      let C = null;
      try {
        C = y(e);
      } catch (N) {
        if (
          (rt(
            "Language definition for '{}' could not be registered.".replace(
              "{}",
              p,
            ),
          ),
          i)
        )
          rt(N);
        else throw N;
        C = a;
      }
      C.name || (C.name = p),
        (t[p] = C),
        (C.rawDefinition = y.bind(null, e)),
        C.aliases && H(C.aliases, { languageName: p });
    }
    function L(p) {
      delete t[p];
      for (const y of Object.keys(n)) n[y] === p && delete n[y];
    }
    function K() {
      return Object.keys(t);
    }
    function P(p) {
      return (p = (p || "").toLowerCase()), t[p] || t[n[p]];
    }
    function H(p, { languageName: y }) {
      typeof p == "string" && (p = [p]),
        p.forEach((C) => {
          n[C.toLowerCase()] = y;
        });
    }
    function q(p) {
      const y = P(p);
      return y && !y.disableAutodetect;
    }
    function z(p) {
      p["before:highlightBlock"] &&
        !p["before:highlightElement"] &&
        (p["before:highlightElement"] = (y) => {
          p["before:highlightBlock"](Object.assign({ block: y.el }, y));
        }),
        p["after:highlightBlock"] &&
          !p["after:highlightElement"] &&
          (p["after:highlightElement"] = (y) => {
            p["after:highlightBlock"](Object.assign({ block: y.el }, y));
          });
    }
    function k(p) {
      z(p), r.push(p);
    }
    function U(p) {
      const y = r.indexOf(p);
      y !== -1 && r.splice(y, 1);
    }
    function Y(p, y) {
      const C = p;
      r.forEach(function (N) {
        N[C] && N[C](y);
      });
    }
    function te(p) {
      return (
        lt("10.7.0", "highlightBlock will be removed entirely in v12.0"),
        lt("10.7.0", "Please use highlightElement now."),
        S(p)
      );
    }
    Object.assign(e, {
      highlight: l,
      highlightAuto: _,
      highlightAll: u,
      highlightElement: S,
      highlightBlock: te,
      configure: h,
      initHighlighting: E,
      initHighlightingOnLoad: O,
      registerLanguage: m,
      unregisterLanguage: L,
      listLanguages: K,
      getLanguage: P,
      registerAliases: H,
      autoDetection: q,
      inherit: bi,
      addPlugin: k,
      removePlugin: U,
    }),
      (e.debugMode = function () {
        i = !1;
      }),
      (e.safeMode = function () {
        i = !0;
      }),
      (e.versionString = Ll),
      (e.regex = {
        concat: at,
        lookahead: Zo,
        either: Ir,
        optional: Yu,
        anyNumberOfTimes: Xu,
      });
    for (const p in Qt) typeof Qt[p] == "object" && Yo(Qt[p]);
    return Object.assign(e, Qt), e;
  },
  bt = sa({});
bt.newInstance = () => sa({});
var Bl = bt;
bt.HighlightJS = bt;
bt.default = bt;
const xn = Ku(Bl);
function $l(e) {
  const t = e.regex,
    n = /(?![A-Za-z0-9])(?![$])/,
    r = t.concat(/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/, n),
    i = t.concat(
      /(\\?[A-Z][a-z0-9_\x7f-\xff]+|\\?[A-Z]+(?=[A-Z][a-z0-9_\x7f-\xff])){1,}/,
      n,
    ),
    o = { scope: "variable", match: "\\$+" + r },
    a = {
      scope: "meta",
      variants: [
        { begin: /<\?php/, relevance: 10 },
        { begin: /<\?=/ },
        { begin: /<\?/, relevance: 0.1 },
        { begin: /\?>/ },
      ],
    },
    s = {
      scope: "subst",
      variants: [{ begin: /\$\w+/ }, { begin: /\{\$/, end: /\}/ }],
    },
    c = e.inherit(e.APOS_STRING_MODE, { illegal: null }),
    f = e.inherit(e.QUOTE_STRING_MODE, {
      illegal: null,
      contains: e.QUOTE_STRING_MODE.contains.concat(s),
    }),
    l = {
      begin: /<<<[ \t]*(?:(\w+)|"(\w+)")\n/,
      end: /[ \t]*(\w+)\b/,
      contains: e.QUOTE_STRING_MODE.contains.concat(s),
      "on:begin": (k, U) => {
        U.data._beginMatch = k[1] || k[2];
      },
      "on:end": (k, U) => {
        U.data._beginMatch !== k[1] && U.ignoreMatch();
      },
    },
    v = e.END_SAME_AS_BEGIN({
      begin: /<<<[ \t]*'(\w+)'\n/,
      end: /[ \t]*(\w+)\b/,
    }),
    b = `[ 	
]`,
    _ = { scope: "string", variants: [f, c, l, v] },
    A = {
      scope: "number",
      variants: [
        { begin: "\\b0[bB][01]+(?:_[01]+)*\\b" },
        { begin: "\\b0[oO][0-7]+(?:_[0-7]+)*\\b" },
        { begin: "\\b0[xX][\\da-fA-F]+(?:_[\\da-fA-F]+)*\\b" },
        {
          begin:
            "(?:\\b\\d+(?:_\\d+)*(\\.(?:\\d+(?:_\\d+)*))?|\\B\\.\\d+)(?:[eE][+-]?\\d+)?",
        },
      ],
      relevance: 0,
    },
    S = ["false", "null", "true"],
    h = [
      "__CLASS__",
      "__DIR__",
      "__FILE__",
      "__FUNCTION__",
      "__COMPILER_HALT_OFFSET__",
      "__LINE__",
      "__METHOD__",
      "__NAMESPACE__",
      "__TRAIT__",
      "die",
      "echo",
      "exit",
      "include",
      "include_once",
      "print",
      "require",
      "require_once",
      "array",
      "abstract",
      "and",
      "as",
      "binary",
      "bool",
      "boolean",
      "break",
      "callable",
      "case",
      "catch",
      "class",
      "clone",
      "const",
      "continue",
      "declare",
      "default",
      "do",
      "double",
      "else",
      "elseif",
      "empty",
      "enddeclare",
      "endfor",
      "endforeach",
      "endif",
      "endswitch",
      "endwhile",
      "enum",
      "eval",
      "extends",
      "final",
      "finally",
      "float",
      "for",
      "foreach",
      "from",
      "global",
      "goto",
      "if",
      "implements",
      "instanceof",
      "insteadof",
      "int",
      "integer",
      "interface",
      "isset",
      "iterable",
      "list",
      "match|0",
      "mixed",
      "new",
      "never",
      "object",
      "or",
      "private",
      "protected",
      "public",
      "readonly",
      "real",
      "return",
      "string",
      "switch",
      "throw",
      "trait",
      "try",
      "unset",
      "use",
      "var",
      "void",
      "while",
      "xor",
      "yield",
    ],
    E = [
      "Error|0",
      "AppendIterator",
      "ArgumentCountError",
      "ArithmeticError",
      "ArrayIterator",
      "ArrayObject",
      "AssertionError",
      "BadFunctionCallException",
      "BadMethodCallException",
      "CachingIterator",
      "CallbackFilterIterator",
      "CompileError",
      "Countable",
      "DirectoryIterator",
      "DivisionByZeroError",
      "DomainException",
      "EmptyIterator",
      "ErrorException",
      "Exception",
      "FilesystemIterator",
      "FilterIterator",
      "GlobIterator",
      "InfiniteIterator",
      "InvalidArgumentException",
      "IteratorIterator",
      "LengthException",
      "LimitIterator",
      "LogicException",
      "MultipleIterator",
      "NoRewindIterator",
      "OutOfBoundsException",
      "OutOfRangeException",
      "OuterIterator",
      "OverflowException",
      "ParentIterator",
      "ParseError",
      "RangeException",
      "RecursiveArrayIterator",
      "RecursiveCachingIterator",
      "RecursiveCallbackFilterIterator",
      "RecursiveDirectoryIterator",
      "RecursiveFilterIterator",
      "RecursiveIterator",
      "RecursiveIteratorIterator",
      "RecursiveRegexIterator",
      "RecursiveTreeIterator",
      "RegexIterator",
      "RuntimeException",
      "SeekableIterator",
      "SplDoublyLinkedList",
      "SplFileInfo",
      "SplFileObject",
      "SplFixedArray",
      "SplHeap",
      "SplMaxHeap",
      "SplMinHeap",
      "SplObjectStorage",
      "SplObserver",
      "SplPriorityQueue",
      "SplQueue",
      "SplStack",
      "SplSubject",
      "SplTempFileObject",
      "TypeError",
      "UnderflowException",
      "UnexpectedValueException",
      "UnhandledMatchError",
      "ArrayAccess",
      "BackedEnum",
      "Closure",
      "Fiber",
      "Generator",
      "Iterator",
      "IteratorAggregate",
      "Serializable",
      "Stringable",
      "Throwable",
      "Traversable",
      "UnitEnum",
      "WeakReference",
      "WeakMap",
      "Directory",
      "__PHP_Incomplete_Class",
      "parent",
      "php_user_filter",
      "self",
      "static",
      "stdClass",
    ],
    M = {
      keyword: h,
      literal: ((k) => {
        const U = [];
        return (
          k.forEach((Y) => {
            U.push(Y),
              Y.toLowerCase() === Y
                ? U.push(Y.toUpperCase())
                : U.push(Y.toLowerCase());
          }),
          U
        );
      })(S),
      built_in: E,
    },
    u = (k) => k.map((U) => U.replace(/\|\d+$/, "")),
    I = {
      variants: [
        {
          match: [
            /new/,
            t.concat(b, "+"),
            t.concat("(?!", u(E).join("\\b|"), "\\b)"),
            i,
          ],
          scope: { 1: "keyword", 4: "title.class" },
        },
      ],
    },
    m = t.concat(r, "\\b(?!\\()"),
    L = {
      variants: [
        {
          match: [t.concat(/::/, t.lookahead(/(?!class\b)/)), m],
          scope: { 2: "variable.constant" },
        },
        { match: [/::/, /class/], scope: { 2: "variable.language" } },
        {
          match: [i, t.concat(/::/, t.lookahead(/(?!class\b)/)), m],
          scope: { 1: "title.class", 3: "variable.constant" },
        },
        {
          match: [i, t.concat("::", t.lookahead(/(?!class\b)/))],
          scope: { 1: "title.class" },
        },
        {
          match: [i, /::/, /class/],
          scope: { 1: "title.class", 3: "variable.language" },
        },
      ],
    },
    K = {
      scope: "attr",
      match: t.concat(r, t.lookahead(":"), t.lookahead(/(?!::)/)),
    },
    P = {
      relevance: 0,
      begin: /\(/,
      end: /\)/,
      keywords: M,
      contains: [K, o, L, e.C_BLOCK_COMMENT_MODE, _, A, I],
    },
    H = {
      relevance: 0,
      match: [
        /\b/,
        t.concat(
          "(?!fn\\b|function\\b|",
          u(h).join("\\b|"),
          "|",
          u(E).join("\\b|"),
          "\\b)",
        ),
        r,
        t.concat(b, "*"),
        t.lookahead(/(?=\()/),
      ],
      scope: { 3: "title.function.invoke" },
      contains: [P],
    };
  P.contains.push(H);
  const q = [K, L, e.C_BLOCK_COMMENT_MODE, _, A, I],
    z = {
      begin: t.concat(/#\[\s*/, i),
      beginScope: "meta",
      end: /]/,
      endScope: "meta",
      keywords: { literal: S, keyword: ["new", "array"] },
      contains: [
        {
          begin: /\[/,
          end: /]/,
          keywords: { literal: S, keyword: ["new", "array"] },
          contains: ["self", ...q],
        },
        ...q,
        { scope: "meta", match: i },
      ],
    };
  return {
    case_insensitive: !1,
    keywords: M,
    contains: [
      z,
      e.HASH_COMMENT_MODE,
      e.COMMENT("//", "$"),
      e.COMMENT("/\\*", "\\*/", {
        contains: [{ scope: "doctag", match: "@[A-Za-z]+" }],
      }),
      {
        match: /__halt_compiler\(\);/,
        keywords: "__halt_compiler",
        starts: {
          scope: "comment",
          end: e.MATCH_NOTHING_RE,
          contains: [{ match: /\?>/, scope: "meta", endsParent: !0 }],
        },
      },
      a,
      { scope: "variable.language", match: /\$this\b/ },
      o,
      H,
      L,
      {
        match: [/const/, /\s/, r],
        scope: { 1: "keyword", 3: "variable.constant" },
      },
      I,
      {
        scope: "function",
        relevance: 0,
        beginKeywords: "fn function",
        end: /[;{]/,
        excludeEnd: !0,
        illegal: "[$%\\[]",
        contains: [
          { beginKeywords: "use" },
          e.UNDERSCORE_TITLE_MODE,
          { begin: "=>", endsParent: !0 },
          {
            scope: "params",
            begin: "\\(",
            end: "\\)",
            excludeBegin: !0,
            excludeEnd: !0,
            keywords: M,
            contains: ["self", o, L, e.C_BLOCK_COMMENT_MODE, _, A],
          },
        ],
      },
      {
        scope: "class",
        variants: [
          { beginKeywords: "enum", illegal: /[($"]/ },
          { beginKeywords: "class interface trait", illegal: /[:($"]/ },
        ],
        relevance: 0,
        end: /\{/,
        excludeEnd: !0,
        contains: [
          { beginKeywords: "extends implements" },
          e.UNDERSCORE_TITLE_MODE,
        ],
      },
      {
        beginKeywords: "namespace",
        relevance: 0,
        end: ";",
        illegal: /[.']/,
        contains: [
          e.inherit(e.UNDERSCORE_TITLE_MODE, { scope: "title.class" }),
        ],
      },
      {
        beginKeywords: "use",
        relevance: 0,
        end: ";",
        contains: [
          { match: /\b(as|const|function)\b/, scope: "keyword" },
          e.UNDERSCORE_TITLE_MODE,
        ],
      },
      _,
      A,
    ],
  };
}
Wu.start();
xn.registerLanguage("php", $l);
window.hljs = xn;
xn.highlightElement(document.querySelector(".default-highlightable-code"));
document.querySelectorAll(".highlightable-code").forEach((e) => {
  e.dataset.highlighted !== "yes" && xn.highlightElement(e);
});
jt("[data-tippy-content]", {
  trigger: "click",
  arrow: !0,
  theme: "material",
  animation: "scale",
});
