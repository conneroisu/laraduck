import { r, j as e, S as D } from "./app-CRe4xN8M.js";
import {
  A as E,
  c as o,
  a as h,
  m as s,
  v as R,
  b as w,
  r as u,
  t as F,
  w as M,
  x as I,
  s as O,
  d as V,
  e as y,
  n as P,
  i as f,
  N as B,
  f as v,
  z as N,
  L as U,
  y as W,
} from "./Tracker-D7VM74TC.js";
function T({ title: a, titleId: n, ...l }, c) {
  return r.createElement(
    "svg",
    Object.assign(
      {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        "data-slot": "icon",
        ref: c,
        "aria-labelledby": n,
      },
      l,
    ),
    a ? r.createElement("title", { id: n }, a) : null,
    r.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3",
    }),
  );
}
const Y = r.forwardRef(T);
function z({ title: a, titleId: n, ...l }, c) {
  return r.createElement(
    "svg",
    Object.assign(
      {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        "data-slot": "icon",
        ref: c,
        "aria-labelledby": n,
      },
      l,
    ),
    a ? r.createElement("title", { id: n }, a) : null,
    r.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5",
    }),
  );
}
const H = r.forwardRef(z);
function Z({ title: a, titleId: n, ...l }, c) {
  return r.createElement(
    "svg",
    Object.assign(
      {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        "data-slot": "icon",
        ref: c,
        "aria-labelledby": n,
      },
      l,
    ),
    a ? r.createElement("title", { id: n }, a) : null,
    r.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z",
    }),
  );
}
const q = r.forwardRef(Z);
function G({ title: a, titleId: n, ...l }, c) {
  return r.createElement(
    "svg",
    Object.assign(
      {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        "data-slot": "icon",
        ref: c,
        "aria-labelledby": n,
      },
      l,
    ),
    a ? r.createElement("title", { id: n }, a) : null,
    r.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z",
    }),
  );
}
const J = r.forwardRef(G),
  X = ({
    filters: a,
    salesTrend: n,
    movingAverages: l,
    categoryAnalysis: c,
    hourlyPatterns: b,
    paymentMethods: g,
  }) => {
    const [j, A] = r.useState(a.period),
      [k, C] = r.useState({
        start: new Date(a.start_date),
        end: new Date(a.end_date),
      }),
      d = (t) =>
        new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: "USD",
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(t),
      m = (t) => new Intl.NumberFormat("en-US").format(t),
      S = (t) => {
        A(t),
          (window.location.href = `?period=${t}&start_date=${a.start_date}&end_date=${a.end_date}`);
      },
      L = (t) => {
        if ((C(t), t.start && t.end)) {
          const i = t.start.toISOString().split("T")[0],
            x = t.end.toISOString().split("T")[0];
          window.location.href = `?period=${j}&start_date=${i}&end_date=${x}`;
        }
      },
      _ = (t) => {
        window.location.href = `/analytics/export?type=sales&format=${t}&start_date=${a.start_date}&end_date=${a.end_date}`;
      },
      p = Array.from({ length: 24 }, (t, i) => ({
        ...(b.find(($) => $.hour === i) || {
          hour: i,
          orders: 0,
          revenue: 0,
          avgOrderValue: 0,
        }),
        hourLabel: `${i.toString().padStart(2, "0")}:00`,
      }));
    return e.jsxs(E, {
      children: [
        e.jsx(D, { title: "Sales Analytics" }),
        e.jsx("div", {
          className: "py-12",
          children: e.jsxs("div", {
            className: "max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6",
            children: [
              e.jsx(o, {
                children: e.jsxs(h, {
                  justifyContent: "between",
                  alignItems: "start",
                  children: [
                    e.jsxs("div", {
                      children: [
                        e.jsx(s, {
                          className: "text-xl font-semibold",
                          children: "Sales Analytics",
                        }),
                        e.jsx(s, {
                          className: "text-sm text-gray-500 mt-1",
                          children: "Comprehensive sales performance analysis",
                        }),
                      ],
                    }),
                    e.jsxs("div", {
                      className: "flex items-center space-x-4",
                      children: [
                        e.jsxs(R, {
                          value: j,
                          onValueChange: S,
                          className: "w-32",
                          icon: w,
                          children: [
                            e.jsx(u, { value: "hour", children: "Hourly" }),
                            e.jsx(u, { value: "day", children: "Daily" }),
                            e.jsx(u, { value: "week", children: "Weekly" }),
                            e.jsx(u, { value: "month", children: "Monthly" }),
                          ],
                        }),
                        e.jsx(F, {
                          value: k,
                          onValueChange: L,
                          selectPlaceholder: "Select",
                          className: "w-64",
                          children: e.jsx(M, {
                            from: new Date(2024, 0, 1),
                            to: new Date(),
                          }),
                        }),
                        e.jsx(I, {
                          size: "sm",
                          variant: "secondary",
                          icon: Y,
                          onClick: () => _("csv"),
                          children: "Export",
                        }),
                      ],
                    }),
                  ],
                }),
              }),
              e.jsxs(O, {
                children: [
                  e.jsxs(V, {
                    children: [
                      e.jsx(y, { icon: w, children: "Trends" }),
                      e.jsx(y, { icon: H, children: "Moving Averages" }),
                    ],
                  }),
                  e.jsxs(P, {
                    children: [
                      e.jsx(f, {
                        children: e.jsxs(o, {
                          children: [
                            e.jsx(s, {
                              className: "text-lg font-semibold mb-4",
                              children: "Sales Performance Trends",
                            }),
                            e.jsx(B, {
                              className: "h-80",
                              data: n,
                              index: "period",
                              categories: ["revenue", "orders", "customers"],
                              colors: ["indigo", "cyan", "amber"],
                              valueFormatter: (t) => (t > 1e3 ? d(t) : m(t)),
                              showLegend: !0,
                              showYAxis: !0,
                              showGradient: !0,
                              showAnimation: !0,
                            }),
                            e.jsxs(v, {
                              numItems: 1,
                              numItemsSm: 3,
                              className: "gap-4 mt-6",
                              children: [
                                e.jsxs(o, {
                                  decoration: "left",
                                  decorationColor: "indigo",
                                  children: [
                                    e.jsx(s, { children: "Total Revenue" }),
                                    e.jsx(s, {
                                      className: "text-2xl font-semibold",
                                      children: d(
                                        n.reduce((t, i) => t + i.revenue, 0),
                                      ),
                                    }),
                                  ],
                                }),
                                e.jsxs(o, {
                                  decoration: "left",
                                  decorationColor: "cyan",
                                  children: [
                                    e.jsx(s, { children: "Total Orders" }),
                                    e.jsx(s, {
                                      className: "text-2xl font-semibold",
                                      children: m(
                                        n.reduce((t, i) => t + i.orders, 0),
                                      ),
                                    }),
                                  ],
                                }),
                                e.jsxs(o, {
                                  decoration: "left",
                                  decorationColor: "amber",
                                  children: [
                                    e.jsx(s, { children: "Unique Customers" }),
                                    e.jsx(s, {
                                      className: "text-2xl font-semibold",
                                      children: m(
                                        n.reduce((t, i) => t + i.customers, 0),
                                      ),
                                    }),
                                  ],
                                }),
                              ],
                            }),
                          ],
                        }),
                      }),
                      e.jsx(f, {
                        children: e.jsxs(o, {
                          children: [
                            e.jsx(s, {
                              className: "text-lg font-semibold mb-4",
                              children: "7-Day Moving Averages",
                            }),
                            e.jsx(N, {
                              className: "h-80",
                              data: l,
                              index: "date",
                              categories: ["dailyRevenue", "movingAvg7d"],
                              colors: ["gray", "indigo"],
                              valueFormatter: d,
                              showLegend: !0,
                              showYAxis: !0,
                              showAnimation: !0,
                            }),
                            e.jsx(s, {
                              className: "text-sm text-gray-500 mt-4",
                              children:
                                "Moving averages help identify trends by smoothing out short-term fluctuations",
                            }),
                          ],
                        }),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(o, {
                children: [
                  e.jsx(s, {
                    className: "text-lg font-semibold mb-4",
                    children: "Category & Brand Performance",
                  }),
                  e.jsx("div", {
                    className: "overflow-x-auto",
                    children: e.jsxs("table", {
                      className: "min-w-full divide-y divide-gray-200",
                      children: [
                        e.jsx("thead", {
                          children: e.jsxs("tr", {
                            children: [
                              e.jsx("th", {
                                className:
                                  "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider",
                                children: "Category / Brand",
                              }),
                              e.jsx("th", {
                                className:
                                  "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                children: "Orders",
                              }),
                              e.jsx("th", {
                                className:
                                  "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                children: "Revenue",
                              }),
                              e.jsx("th", {
                                className:
                                  "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                children: "Units Sold",
                              }),
                              e.jsx("th", {
                                className:
                                  "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                children: "Avg Discount",
                              }),
                            ],
                          }),
                        }),
                        e.jsx("tbody", {
                          className: "bg-white divide-y divide-gray-200",
                          children: c.map((t, i) => {
                            var x;
                            return e.jsxs(
                              "tr",
                              {
                                className:
                                  i % 2 === 0 ? "bg-white" : "bg-gray-50",
                                children: [
                                  e.jsx("td", {
                                    className: "px-6 py-4 whitespace-nowrap",
                                    children: e.jsxs("div", {
                                      children: [
                                        e.jsx(s, {
                                          className: "font-medium",
                                          children: t.category,
                                        }),
                                        e.jsx(s, {
                                          className: "text-sm text-gray-500",
                                          children: t.brand,
                                        }),
                                      ],
                                    }),
                                  }),
                                  e.jsx("td", {
                                    className:
                                      "px-6 py-4 whitespace-nowrap text-right",
                                    children: m(t.orders),
                                  }),
                                  e.jsx("td", {
                                    className:
                                      "px-6 py-4 whitespace-nowrap text-right font-medium",
                                    children: d(t.revenue),
                                  }),
                                  e.jsx("td", {
                                    className:
                                      "px-6 py-4 whitespace-nowrap text-right",
                                    children: m(t.units_sold),
                                  }),
                                  e.jsxs("td", {
                                    className:
                                      "px-6 py-4 whitespace-nowrap text-right",
                                    children: [
                                      (x = t.avg_discount_percent) == null
                                        ? void 0
                                        : x.toFixed(1),
                                      "%",
                                    ],
                                  }),
                                ],
                              },
                              i,
                            );
                          }),
                        }),
                      ],
                    }),
                  }),
                ],
              }),
              e.jsxs(v, {
                numItems: 1,
                numItemsLg: 2,
                className: "gap-6",
                children: [
                  e.jsxs(o, {
                    children: [
                      e.jsxs(h, {
                        justifyContent: "between",
                        alignItems: "center",
                        className: "mb-4",
                        children: [
                          e.jsx(s, {
                            className: "text-lg font-semibold",
                            children: "Hourly Sales Patterns",
                          }),
                          e.jsx(q, { className: "h-5 w-5 text-gray-400" }),
                        ],
                      }),
                      e.jsx(U, {
                        className: "h-72",
                        data: p,
                        index: "hourLabel",
                        categories: ["revenue"],
                        colors: ["indigo"],
                        valueFormatter: d,
                        showLegend: !1,
                        showAnimation: !0,
                      }),
                      e.jsxs(s, {
                        className: "text-sm text-gray-500 mt-4",
                        children: [
                          "Peak hours: ",
                          p
                            .slice()
                            .sort((t, i) => i.revenue - t.revenue)
                            .slice(0, 3)
                            .map((t) => t.hourLabel)
                            .join(", "),
                        ],
                      }),
                    ],
                  }),
                  e.jsxs(o, {
                    children: [
                      e.jsxs(h, {
                        justifyContent: "between",
                        alignItems: "center",
                        className: "mb-4",
                        children: [
                          e.jsx(s, {
                            className: "text-lg font-semibold",
                            children: "Payment Method Distribution",
                          }),
                          e.jsx(J, { className: "h-5 w-5 text-gray-400" }),
                        ],
                      }),
                      e.jsx(W, {
                        className: "h-64",
                        data: g,
                        category: "revenue",
                        index: "payment_method",
                        valueFormatter: d,
                        colors: ["slate", "violet", "indigo", "rose", "cyan"],
                        showAnimation: !0,
                      }),
                      e.jsx("div", {
                        className: "mt-4 space-y-2",
                        children: g.map((t) =>
                          e.jsxs(
                            h,
                            {
                              justifyContent: "between",
                              children: [
                                e.jsx(s, { children: t.payment_method }),
                                e.jsxs("div", {
                                  className: "text-right",
                                  children: [
                                    e.jsx(s, {
                                      className: "font-medium",
                                      children: d(t.revenue),
                                    }),
                                    e.jsxs(s, {
                                      className: "text-xs text-gray-500",
                                      children: [
                                        m(t.transactions),
                                        " transactions",
                                      ],
                                    }),
                                  ],
                                }),
                              ],
                            },
                            t.payment_method,
                          ),
                        ),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(o, {
                children: [
                  e.jsx(s, {
                    className: "text-lg font-semibold mb-4",
                    children: "Average Order Value Trend",
                  }),
                  e.jsx(N, {
                    className: "h-64",
                    data: n,
                    index: "period",
                    categories: ["avgOrderValue"],
                    colors: ["emerald"],
                    valueFormatter: d,
                    showLegend: !1,
                    showYAxis: !0,
                    showAnimation: !0,
                  }),
                ],
              }),
            ],
          }),
        }),
      ],
    });
  };
export { X as default };
