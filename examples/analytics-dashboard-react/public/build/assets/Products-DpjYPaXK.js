import { r as l, j as e, S as R } from "./app-CRe4xN8M.js";
import {
  A as C,
  c as n,
  a as c,
  m as t,
  o as S,
  f as h,
  p as j,
  g as v,
  q as f,
  y as F,
  s as A,
  d as P,
  e as N,
  n as L,
  i as w,
  V as M,
  h as E,
  L as $,
} from "./Tracker-D7VM74TC.js";
import { F as b } from "./ArrowTrendingUpIcon-Cf1rCHJL.js";
function D({ title: o, titleId: i, ...d }, x) {
  return l.createElement(
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
        ref: x,
        "aria-labelledby": i,
      },
      d,
    ),
    o ? l.createElement("title", { id: i }, o) : null,
    l.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99",
    }),
  );
}
const B = l.forwardRef(D);
function O({ title: o, titleId: i, ...d }, x) {
  return l.createElement(
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
        ref: x,
        "aria-labelledby": i,
      },
      d,
    ),
    o ? l.createElement("title", { id: i }, o) : null,
    l.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z",
    }),
    l.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M6 6h.008v.008H6V6Z",
    }),
  );
}
const T = l.forwardRef(O),
  H = ({
    filters: o,
    productPerformance: i,
    abcAnalysis: d,
    inventoryTurnover: x,
    categoryPivot: k,
  }) => {
    const a = (s) =>
        new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: "USD",
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(s),
      u = (s) => new Intl.NumberFormat("en-US").format(s),
      I = Object.values(d).map((s) => ({
        category: s.category,
        productCount: s.productCount,
        revenue: s.totalRevenue,
        percentage: s.revenuePercentage,
      })),
      m = i.reduce(
        (s, r) => ({
          revenue: s.revenue + r.revenue,
          units: s.units + r.unitsSold,
          orders: s.orders + r.orders,
          profit: s.profit + r.profit,
        }),
        { revenue: 0, units: 0, orders: 0, profit: 0 },
      ),
      g = k.map((s) => ({
        category: s.category,
        North: s.north || 0,
        South: s.south || 0,
        East: s.east || 0,
        West: s.west || 0,
        total: s.total || 0,
      }));
    return e.jsxs(C, {
      children: [
        e.jsx(R, { title: "Product Analytics" }),
        e.jsx("div", {
          className: "py-12",
          children: e.jsxs("div", {
            className: "max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6",
            children: [
              e.jsx(n, {
                children: e.jsxs(c, {
                  justifyContent: "between",
                  alignItems: "center",
                  children: [
                    e.jsxs("div", {
                      children: [
                        e.jsx(t, {
                          className: "text-xl font-semibold",
                          children: "Product Analytics",
                        }),
                        e.jsx(t, {
                          className: "text-sm text-gray-500 mt-1",
                          children:
                            "Product performance, inventory management, and profitability analysis",
                        }),
                      ],
                    }),
                    e.jsx(S, { className: "h-8 w-8 text-gray-400" }),
                  ],
                }),
              }),
              e.jsxs(h, {
                numItems: 1,
                numItemsSm: 2,
                numItemsLg: 4,
                className: "gap-6",
                children: [
                  e.jsx(n, {
                    children: e.jsxs(c, {
                      justifyContent: "between",
                      alignItems: "start",
                      children: [
                        e.jsxs("div", {
                          children: [
                            e.jsx(t, { children: "Total Revenue" }),
                            e.jsx(j, { children: a(m.revenue) }),
                          ],
                        }),
                        e.jsx(v, {
                          color: "emerald",
                          icon: b,
                          children: "+12.5%",
                        }),
                      ],
                    }),
                  }),
                  e.jsx(n, {
                    children: e.jsxs(c, {
                      justifyContent: "between",
                      alignItems: "start",
                      children: [
                        e.jsxs("div", {
                          children: [
                            e.jsx(t, { children: "Total Profit" }),
                            e.jsx(j, { children: a(m.profit) }),
                            e.jsxs(t, {
                              className: "text-sm text-gray-500 mt-1",
                              children: [
                                ((m.profit / m.revenue) * 100).toFixed(1),
                                "% margin",
                              ],
                            }),
                          ],
                        }),
                        e.jsx(f, { className: "h-8 w-8 text-gray-400" }),
                      ],
                    }),
                  }),
                  e.jsx(n, {
                    children: e.jsxs(c, {
                      justifyContent: "between",
                      alignItems: "start",
                      children: [
                        e.jsxs("div", {
                          children: [
                            e.jsx(t, { children: "Units Sold" }),
                            e.jsx(j, { children: u(m.units) }),
                          ],
                        }),
                        e.jsx(v, { color: "blue", icon: b, children: "+8.2%" }),
                      ],
                    }),
                  }),
                  e.jsx(n, {
                    children: e.jsxs(c, {
                      justifyContent: "between",
                      alignItems: "start",
                      children: [
                        e.jsxs("div", {
                          children: [
                            e.jsx(t, { children: "Active Products" }),
                            e.jsx(j, { children: i.length }),
                          ],
                        }),
                        e.jsx(T, { className: "h-8 w-8 text-gray-400" }),
                      ],
                    }),
                  }),
                ],
              }),
              e.jsxs(n, {
                children: [
                  e.jsx(t, {
                    className: "text-lg font-semibold mb-4",
                    children: "ABC Analysis",
                  }),
                  e.jsx(t, {
                    className: "text-sm text-gray-500 mb-6",
                    children: "Product classification by revenue contribution",
                  }),
                  e.jsxs(h, {
                    numItems: 1,
                    numItemsLg: 2,
                    className: "gap-6 mb-6",
                    children: [
                      e.jsx(F, {
                        className: "h-72",
                        data: I,
                        category: "revenue",
                        index: "category",
                        valueFormatter: a,
                        colors: ["emerald", "amber", "rose"],
                        showAnimation: !0,
                      }),
                      e.jsxs("div", {
                        className: "space-y-4",
                        children: [
                          Object.entries(d).map(([s, r]) =>
                            e.jsx(
                              "div",
                              {
                                className: "p-4 border rounded-lg",
                                children: e.jsxs(c, {
                                  justifyContent: "between",
                                  alignItems: "center",
                                  children: [
                                    e.jsxs("div", {
                                      className: "flex items-center space-x-3",
                                      children: [
                                        e.jsx("div", {
                                          className: `w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold
                                                    ${s === "A" ? "bg-emerald-500" : s === "B" ? "bg-amber-500" : "bg-rose-500"}`,
                                          children: s,
                                        }),
                                        e.jsxs("div", {
                                          children: [
                                            e.jsxs(t, {
                                              className: "font-semibold",
                                              children: ["Category ", s],
                                            }),
                                            e.jsxs(t, {
                                              className:
                                                "text-sm text-gray-500",
                                              children: [
                                                r.productCount,
                                                " products (",
                                                r.revenuePercentage,
                                                "% of revenue)",
                                              ],
                                            }),
                                          ],
                                        }),
                                      ],
                                    }),
                                    e.jsx(t, {
                                      className: "font-bold text-lg",
                                      children: a(r.totalRevenue),
                                    }),
                                  ],
                                }),
                              },
                              s,
                            ),
                          ),
                          e.jsxs("div", {
                            className: "mt-4 p-4 bg-gray-50 rounded-lg",
                            children: [
                              e.jsx(t, {
                                className: "text-sm font-medium mb-2",
                                children: "ABC Analysis Insights:",
                              }),
                              e.jsxs("ul", {
                                className: "text-sm text-gray-600 space-y-1",
                                children: [
                                  e.jsx("li", {
                                    children:
                                      "• Category A: High-value items requiring tight control",
                                  }),
                                  e.jsx("li", {
                                    children:
                                      "• Category B: Moderate-value items with normal controls",
                                  }),
                                  e.jsx("li", {
                                    children:
                                      "• Category C: Low-value items with simple controls",
                                  }),
                                ],
                              }),
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(A, {
                children: [
                  e.jsxs(P, {
                    children: [
                      e.jsx(N, { icon: f, children: "Performance Details" }),
                      e.jsx(N, { icon: B, children: "Inventory Turnover" }),
                    ],
                  }),
                  e.jsxs(L, {
                    children: [
                      e.jsx(w, {
                        children: e.jsxs(n, {
                          children: [
                            e.jsx(t, {
                              className: "text-lg font-semibold mb-4",
                              children: "Product Performance Details",
                            }),
                            e.jsx("div", {
                              className: "overflow-x-auto",
                              children: e.jsxs("table", {
                                className:
                                  "min-w-full divide-y divide-gray-200",
                                children: [
                                  e.jsx("thead", {
                                    children: e.jsxs("tr", {
                                      children: [
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Product",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Category / Brand",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Price",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Units Sold",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Revenue",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Profit",
                                        }),
                                        e.jsx("th", {
                                          className:
                                            "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                          children: "Margin %",
                                        }),
                                      ],
                                    }),
                                  }),
                                  e.jsx("tbody", {
                                    className:
                                      "bg-white divide-y divide-gray-200",
                                    children: i
                                      .slice(0, 20)
                                      .map((s, r) =>
                                        e.jsxs(
                                          "tr",
                                          {
                                            className:
                                              r % 2 === 0
                                                ? "bg-white"
                                                : "bg-gray-50",
                                            children: [
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap",
                                                children: e.jsx(t, {
                                                  className: "font-medium",
                                                  children: s.name,
                                                }),
                                              }),
                                              e.jsxs("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap",
                                                children: [
                                                  e.jsx(t, {
                                                    className: "text-sm",
                                                    children: s.category,
                                                  }),
                                                  e.jsx(t, {
                                                    className:
                                                      "text-xs text-gray-500",
                                                    children: s.brand,
                                                  }),
                                                ],
                                              }),
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap text-right",
                                                children: a(s.price),
                                              }),
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap text-right",
                                                children: u(s.unitsSold),
                                              }),
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap text-right font-medium",
                                                children: a(s.revenue),
                                              }),
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap text-right",
                                                children: a(s.profit),
                                              }),
                                              e.jsx("td", {
                                                className:
                                                  "px-6 py-4 whitespace-nowrap text-right",
                                                children: e.jsxs(v, {
                                                  color:
                                                    s.margin > 30
                                                      ? "emerald"
                                                      : s.margin > 20
                                                        ? "amber"
                                                        : "rose",
                                                  children: [
                                                    s.margin.toFixed(1),
                                                    "%",
                                                  ],
                                                }),
                                              }),
                                            ],
                                          },
                                          s.productId,
                                        ),
                                      ),
                                  }),
                                ],
                              }),
                            }),
                          ],
                        }),
                      }),
                      e.jsx(w, {
                        children: e.jsxs(n, {
                          children: [
                            e.jsx(t, {
                              className: "text-lg font-semibold mb-4",
                              children: "Inventory Turnover Analysis",
                            }),
                            e.jsx(t, {
                              className: "text-sm text-gray-500 mb-6",
                              children:
                                "Products ranked by inventory turnover rate (annualized)",
                            }),
                            e.jsx(M, {
                              className: "h-80 mb-6",
                              data: x,
                              x: "stockQuantity",
                              y: "turnoverRate",
                              size: "stockValue",
                              category: "category",
                              colors: [
                                "indigo",
                                "cyan",
                                "amber",
                                "rose",
                                "emerald",
                              ],
                              valueFormatter: (s) => s.toFixed(2),
                              showLegend: !0,
                              showAnimation: !0,
                            }),
                            e.jsx("div", {
                              className: "space-y-4",
                              children: x
                                .slice(0, 10)
                                .map((s, r) =>
                                  e.jsxs(
                                    "div",
                                    {
                                      className: "p-4 border rounded-lg",
                                      children: [
                                        e.jsxs(c, {
                                          justifyContent: "between",
                                          alignItems: "start",
                                          children: [
                                            e.jsxs("div", {
                                              className: "flex-1",
                                              children: [
                                                e.jsx(t, {
                                                  className: "font-semibold",
                                                  children: s.name,
                                                }),
                                                e.jsx(t, {
                                                  className:
                                                    "text-sm text-gray-500",
                                                  children: s.category,
                                                }),
                                                e.jsxs(h, {
                                                  numItems: 4,
                                                  className: "gap-4 mt-3",
                                                  children: [
                                                    e.jsxs("div", {
                                                      children: [
                                                        e.jsx(t, {
                                                          className:
                                                            "text-xs text-gray-500",
                                                          children: "Stock Qty",
                                                        }),
                                                        e.jsx(t, {
                                                          className:
                                                            "font-medium",
                                                          children: u(
                                                            s.stockQuantity,
                                                          ),
                                                        }),
                                                      ],
                                                    }),
                                                    e.jsxs("div", {
                                                      children: [
                                                        e.jsx(t, {
                                                          className:
                                                            "text-xs text-gray-500",
                                                          children:
                                                            "Units/Month",
                                                        }),
                                                        e.jsx(t, {
                                                          className:
                                                            "font-medium",
                                                          children: Math.round(
                                                            (s.unitsSold / 30) *
                                                              30,
                                                          ),
                                                        }),
                                                      ],
                                                    }),
                                                    e.jsxs("div", {
                                                      children: [
                                                        e.jsx(t, {
                                                          className:
                                                            "text-xs text-gray-500",
                                                          children:
                                                            "Turnover Rate",
                                                        }),
                                                        e.jsxs(t, {
                                                          className:
                                                            "font-medium",
                                                          children: [
                                                            s.turnoverRate.toFixed(
                                                              2,
                                                            ),
                                                            "x",
                                                          ],
                                                        }),
                                                      ],
                                                    }),
                                                    e.jsxs("div", {
                                                      children: [
                                                        e.jsx(t, {
                                                          className:
                                                            "text-xs text-gray-500",
                                                          children:
                                                            "Days Supply",
                                                        }),
                                                        e.jsx(t, {
                                                          className:
                                                            "font-medium",
                                                          children:
                                                            s.daysOfSupply
                                                              ? `${Math.round(s.daysOfSupply)} days`
                                                              : "N/A",
                                                        }),
                                                      ],
                                                    }),
                                                  ],
                                                }),
                                              ],
                                            }),
                                            e.jsxs("div", {
                                              className: "text-right",
                                              children: [
                                                e.jsx(t, {
                                                  className:
                                                    "text-sm text-gray-500",
                                                  children: "Stock Value",
                                                }),
                                                e.jsx(t, {
                                                  className: "font-bold",
                                                  children: a(s.stockValue),
                                                }),
                                              ],
                                            }),
                                          ],
                                        }),
                                        e.jsxs("div", {
                                          className: "mt-3",
                                          children: [
                                            e.jsxs(c, {
                                              justifyContent: "between",
                                              className: "mb-1",
                                              children: [
                                                e.jsx(t, {
                                                  className:
                                                    "text-xs text-gray-500",
                                                  children: "Inventory Health",
                                                }),
                                                e.jsx(t, {
                                                  className:
                                                    "text-xs font-medium",
                                                  children:
                                                    s.turnoverRate > 12
                                                      ? "Fast Moving"
                                                      : s.turnoverRate > 6
                                                        ? "Normal"
                                                        : s.turnoverRate > 2
                                                          ? "Slow Moving"
                                                          : "Dead Stock",
                                                }),
                                              ],
                                            }),
                                            e.jsx(E, {
                                              value: Math.min(
                                                s.turnoverRate * 8,
                                                100,
                                              ),
                                              color:
                                                s.turnoverRate > 12
                                                  ? "emerald"
                                                  : s.turnoverRate > 6
                                                    ? "blue"
                                                    : s.turnoverRate > 2
                                                      ? "amber"
                                                      : "rose",
                                            }),
                                          ],
                                        }),
                                      ],
                                    },
                                    s.productId,
                                  ),
                                ),
                            }),
                          ],
                        }),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(n, {
                children: [
                  e.jsx(t, {
                    className: "text-lg font-semibold mb-4",
                    children: "Category Performance by Region",
                  }),
                  e.jsx($, {
                    className: "h-80",
                    data: g,
                    index: "category",
                    categories: ["North", "South", "East", "West"],
                    colors: ["slate", "violet", "indigo", "rose"],
                    valueFormatter: a,
                    stack: !0,
                    showLegend: !0,
                    showAnimation: !0,
                  }),
                  e.jsx(h, {
                    numItems: 1,
                    numItemsSm: 2,
                    numItemsLg: 4,
                    className: "gap-4 mt-6",
                    children: ["North", "South", "East", "West"].map((s) => {
                      const r = g.reduce((y, p) => y + (p[s] || 0), 0);
                      return e.jsxs(
                        n,
                        {
                          children: [
                            e.jsxs(t, {
                              className: "text-sm text-gray-500",
                              children: [s, " Region"],
                            }),
                            e.jsx(t, {
                              className: "text-xl font-semibold mt-1",
                              children: a(r),
                            }),
                            e.jsxs(t, {
                              className: "text-sm mt-2",
                              children: [
                                (
                                  (r / g.reduce((y, p) => y + p.total, 0)) *
                                  100
                                ).toFixed(1),
                                "% of total",
                              ],
                            }),
                          ],
                        },
                        s,
                      );
                    }),
                  }),
                ],
              }),
            ],
          }),
        }),
      ],
    });
  };
export { H as default };
