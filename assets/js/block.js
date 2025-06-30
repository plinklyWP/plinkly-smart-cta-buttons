/* =========================================================================
 * PlinkLy CTA Buttons – Gutenberg Block
 * Version 1.5 (Gemini API Protection - HMAC Signature + Authorization + Nonce + Proxy Token)
 * ========================================================================= */
(function (blocks, blockEditor, element, components) {
  /* ========== WordPress Shortcuts ========== */
  const el = element.createElement;
  const { InspectorControls, MediaUpload, MediaUploadCheck } = blockEditor;
  const {
    PanelBody,
    PanelRow,
    TextControl,
    ToggleControl,
    Button,
    ColorPalette,
    TabPanel,
    SelectControl,
    RangeControl,
    Notice,
  } = components;
  const ServerSideRender = wp.serverSideRender;

  /* ========== PHP Defined Data ========== */
  const defaults = window.PlinkLyDefaults || {};
  const companyColors = window.PlinkLyCompanyColors || {};
  const {
    aiProxyUrl,
    proxyToken,
    licenseKey,
    hmacSecret,
    nonce,
    isPro = false,
    abEnabled = 0
  } = window.PlinkLyConfig || {};

  /* ========== A utility to load CryptoJS when needed ========== */
  let CryptoJS = window.CryptoJS || null;
  const ensureCryptoJS = () =>
    new Promise((resolve, reject) => {
      if (CryptoJS) return resolve(CryptoJS);
      const scr = document.createElement("script");
      scr.src =
        window.PlinkLyAssets?.cryptoJsUrl ||
        "/wp-content/plugins/plinkly/assets/js/crypto-js.min.js";
      scr.onload = () => {
        CryptoJS = window.CryptoJS;
        resolve(CryptoJS);
      };
      scr.onerror = () => reject(new Error("Failed to load Crypto‑JS"));
      document.head.appendChild(scr);
    });

  const getHost = (urlStr) => {
    try {
      if (urlStr && !/^https?:\/\//i.test(urlStr)) urlStr = "https://" + urlStr;
      return new URL(urlStr).hostname.replace(/^www\./i, "");
    } catch (_) {
      return "";
    }
  };

  const freshButton = () => ({
    text: "Buy Now",
    link: "",
    openInNewTab: !!defaults.defaultNewTab,
    iconUrl: "",
    iconId: 0,
    customColor: "",
    borderStyle: defaults.defaultBorderStyle ?? "none",
    borderWidth: defaults.defaultBorderWidth ?? 0,
    borderColor: defaults.defaultBorderColor ?? "#CCCCCC",
    borderRadius: defaults.defaultBorderRadius ?? 5,
    shadowOffsetX: defaults.defaultShadowOffsetX ?? 0,
    shadowOffsetY: defaults.defaultShadowOffsetY ?? 0,
    shadowBlur: defaults.defaultShadowBlur ?? 0,
    shadowColor: defaults.defaultShadowColor ?? "#000000",
    fontFamily: defaults.defaultFontFamily ?? "",
    fontWeight: defaults.defaultFontWeight ?? "",
    lineHeight: defaults.defaultLineHeight ?? "",
    fontSize: defaults.defaultFontSize ?? 16,
    paddingV: defaults.defaultPaddingV ?? 12,
    paddingH: defaults.defaultPaddingH ?? 26,
    logoSize: defaults.defaultLogoSize ?? 24,
    abTestEnabled: false,
abButtonText: "",
abCustomColor: ""
  });

  /* =====================================================================
   * Block definition – custom/affiliate-buttons-group
   * ===================================================================== */
  blocks.registerBlockType("custom/affiliate-buttons-group", {
    title: "PlinkLy CTA Buttons Group",
    icon: "cart",
    category: "common",

    attributes: {
      buttons: { type: "array", default: [] },
      layout: { type: "string", default: "horizontal" },
      alignment: { type: "string", default: "left" },
      gapHorizontal: { type: "number", default: defaults.defaultGapH ?? 10 },
      gapVertical: { type: "number", default: defaults.defaultGapV ?? 10 },
    },

    edit(props) {
      const { buttons, layout, alignment, gapHorizontal, gapVertical } =
        props.attributes;
        
                // إصلاح جميع الأزرار القديمة عند التحميل
if (buttons.some(btn => btn.abTestEnabled === undefined)) {
  props.setAttributes({
    buttons: buttons.map(btn => ({
      abTestEnabled: false,
      abButtonText: "",
      abCustomColor: "",
      ...btn
    }))
  });
}

      if (buttons.length === 0) {
        props.setAttributes({ buttons: [freshButton()] });
        return null;
      }

      const getEffectiveColor = (btn) => {
        if (btn.customColor?.trim()) return btn.customColor;
        const host = getHost(btn.link || "");
        if (host && companyColors[host]?.color)
          return companyColors[host].color;
        return defaults.defaultColor || "#3498db";
      };

     const updateButton = (i, field, val) =>
  props.setAttributes({
    buttons: buttons.map((b, idx) => {
      // ضمن وجود الخصائص دائما في كل زر
      const safeButton = {
        abTestEnabled: false,
        abButtonText: "",
        abCustomColor: "",
        ...b
      };
      if (idx !== i) return safeButton;
      return { ...safeButton, [field]: val };
    }),
  });



      const applyStyleToAll = (sourceIndex) => {
        const source = buttons[sourceIndex];
        props.setAttributes({
          buttons: buttons.map((b, idx) =>
            idx === sourceIndex
              ? b
              : {
                  ...b,
                  customColor: source.customColor,
                  borderStyle: source.borderStyle,
                  borderWidth: source.borderWidth,
                  borderColor: source.borderColor,
                  borderRadius: source.borderRadius,
                  fontFamily: source.fontFamily,
                  fontSize: source.fontSize,
                  fontWeight: source.fontWeight,
                  lineHeight: source.lineHeight,
                  paddingV: source.paddingV,
                  paddingH: source.paddingH,
                  shadowOffsetX: source.shadowOffsetX,
                  shadowOffsetY: source.shadowOffsetY,
                  shadowBlur: source.shadowBlur,
                  shadowColor: source.shadowColor,
                },
          ),
        });
      };

      const addNew = () =>
        props.setAttributes({ buttons: [...buttons, freshButton()] });
      const remove = (i) =>
        props.setAttributes({ buttons: buttons.filter((_, idx) => idx !== i) });

      /* ========== The function that connects to the Proxy ========== */
      const generateCTA = async (rawLink) => {
        await ensureCryptoJS();

        /* 1. Normalize the URL */
        let link = rawLink.trim();
        if (!/^https?:\/\//i.test(link)) link = "https://" + link;

        /* 2. Calculate the signature */
        const sig = CryptoJS.HmacSHA256(link, hmacSecret).toString();

        /* 3. Prepare the body */
        const body = new URLSearchParams({
          link,
          license_key: licenseKey,
          sig,
          nonce,
        });

        /* 4. Send the request */
        const res = await fetch(aiProxyUrl, {
          method: "POST",
          mode: "cors",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-PlinkLy-Token": proxyToken,
          },
          body,
        });

        if (!res.ok) {
          const txt = await res.text();
          throw new Error(`${res.status} – ${txt}`);
        }
        return res.json(); // { cta: '...' }
      };

      return el(
        "div",
        {},
        el(
          InspectorControls,
          {},
          el(
            TabPanel,
            {
              className: "plinkly-tabs",
              tabs: [
                { name: "buttons", title: "Buttons" },
                { name: "style", title: "Style" },
                { name: "settings", title: "Settings" },
              ],
            },
            (tab) => {
              /* ---------- Tab 1 ---------- */
              if (tab.name === "buttons") {
                return el(
                  PanelBody,
                  { title: "Manage Buttons", initialOpen: true },
                  [
                    ...buttons.map((btn, i) =>
                      el(
                        PanelBody,
                        {
                          key: `btn-${i}`,
                          title: `Button ${i + 1}`,
                          initialOpen: false,
                        },
                        [
                          el(TextControl, {
                            label: "Text",
                            value: btn.text,
                            onChange: (v) => updateButton(i, "text", v),
                          }),
                          el(TextControl, {
                            label: "Link (incl. http:// or https://)",
                            value: btn.link,
                            onChange: (v) => updateButton(i, "link", v),
                          }),
                          /* ——— A/B-Test controls ——— */
abEnabled && [
  el(ToggleControl, {
    label: "Enable A/B Test",
    checked: btn.abTestEnabled,
    onChange: (v) => updateButton(i, "abTestEnabled", v),
  }),
  btn.abTestEnabled && el(
    "div",
    { style:{marginLeft:18,padding:8,background:"#f6f6ff",borderRadius:8,marginBottom:8} },
    [
      el("strong", {}, "Variant B Settings"),
      el(TextControl, {
        label: "Button Text (B)",
        value: btn.abButtonText,
        onChange: (v) => updateButton(i, "abButtonText", v),
      }),
      el("p", {}, "Background Color (B):"),
      el(ColorPalette, {
        value: btn.abCustomColor,
        onChange: (v) => updateButton(i, "abCustomColor", v),
      }),
    ]
  ),
],
                          (() => {
                            const host = getHost(btn.link || "");
                            const unknownHost = host && !companyColors[host]; // غير معروف فى المكتبة
                            if (unknownHost && !isPro) {
                              return el(
                                Notice,
                                {
                                  status: "info",
                                  isDismissible: false,
                                  style: { marginTop: 6 },
                                },
                                "This brand is available in Plinkly Pro. Upgrade to unlock 1,000+ brand presets and advanced analytics.",
                              );
                            }
                            return null;
                          })(),
                          el(
                            Button,
                            {
                              isSecondary: true,
                              onClick: async (e) => {
                                const link = buttons[i].link;
                                if (!link) {
                                  alert("Please enter the URL first.");
                                  return;
                                }

                                const originalLabel = e.target.innerText;
                                updateButton(i, "text", "Generating...");
                                e.target.disabled = true;
                                e.target.innerText = "Generating...";

                                try {
                                  const data = await generateCTA(link);
                                  updateButton(
                                    i,
                                    "text",
                                    data.cta ?? "CTA is currently unavailable.",
                                  );
                                } catch (err) {
                                  console.error("AI generation error:", err);
                                  updateButton(
                                    i,
                                    "text",
                                    err.message.includes("429")
                                      ? "Daily quota exceeded."
                                      : "Generation failed.",
                                  );
                                } finally {
                                  e.target.disabled = false;
                                  e.target.innerText = originalLabel;
                                }
                              },
                            },
                            "Suggest a CTA using AI",
                          ),
                          el(
                            Button,
                            {
                              isDestructive: true,
                              onClick: () => remove(i),
                            },
                            "Delete Button",
                          ),
                        ],
                      ),
                    ),
                    el(
                      Button,
                      {
                        isPrimary: true,
                        onClick: addNew,
                        style: { marginTop: "10px" },
                      },
                      "Add New Button",
                    ),
                  ],
                );
              }

              /* ---------- Tab 2 ---------- */
              if (tab.name === "style") {
                return el(
                  InspectorControls,
                  null,
                  el(
                    PanelBody,
                    { title: "Customize Style", initialOpen: true },
                    buttons.map((btn, i) => {
                      const id = `style-${i}`;
                      const onChange = (field) => (value) =>
                        updateButton(i, field, value);

                      const renderSection = (title, initialOpen, children) =>
                        el(
                          PanelBody,
                          { title, initialOpen, key: `${id}-${title}` },
                          children,
                        );

                      return renderSection(`Button ${i + 1} Style`, false, [
                        // —— Background & Border ——
                        renderSection("Background & Border", true, [
                          el("p", {}, "Background Color:"),
                          el(ColorPalette, {
                            key: getEffectiveColor(btn),
                            value: getEffectiveColor(btn),
                            onChange: onChange("customColor"),
                          }),
                          el("p", {}, "Border Style:"),
                          el(SelectControl, {
                            value: btn.borderStyle,
                            options: [
                              { label: "None", value: "none" },
                              { label: "Solid", value: "solid" },
                              { label: "Dotted", value: "dotted" },
                              { label: "Dashed", value: "dashed" },
                            ],
                            onChange: onChange("borderStyle"),
                          }),
                          el(RangeControl, {
                            label: "Border Width",
                            value: btn.borderWidth,
                            min: 0,
                            max: 10,
                            onChange: onChange("borderWidth"),
                          }),
                          el("p", {}, "Border Color:"),
                          el(ColorPalette, {
                            key: btn.borderColor,
                            value: btn.borderColor,
                            onChange: onChange("borderColor"),
                          }),
                          el(RangeControl, {
                            label: "Border Radius",
                            value: btn.borderRadius,
                            min: 0,
                            max: 50,
                            onChange: onChange("borderRadius"),
                          }),
                        ]),

                        // —— Typography ——
                        renderSection("Typography", false, [
                          el("p", {}, "Font Family:"),
                          el(SelectControl, {
                            value: btn.fontFamily,
                            options: [
                              { label: "Default", value: "" },
                              { label: "Arial", value: "Arial" },
                              { label: "Georgia", value: "Georgia" },
                              { label: "Tahoma", value: "Tahoma" },
                              { label: "Times", value: '"Times New Roman"' },
                              { label: "Verdana", value: "Verdana" },
                            ],
                            onChange: onChange("fontFamily"),
                          }),
                          el("p", {}, "Font Size (px):"),
                          el(RangeControl, {
                            value: btn.fontSize || 16,
                            min: 10,
                            max: 40,
                            onChange: onChange("fontSize"),
                          }),
                          el("p", {}, "Font Weight:"),
                          el(SelectControl, {
                            value: btn.fontWeight,
                            options: [
                              { label: "Default", value: "" },
                              { label: "400 (Normal)", value: "400" },
                              { label: "500", value: "500" },
                              { label: "600", value: "600" },
                              { label: "700 (Bold)", value: "700" },
                              { label: "900", value: "900" },
                            ],
                            onChange: onChange("fontWeight"),
                          }),
                          el("p", {}, "Line Height:"),
                          el(RangeControl, {
                            label: "",
                            value: btn.lineHeight || 1,
                            min: 1,
                            max: 3,
                            step: 0.1,
                            onChange: onChange("lineHeight"),
                          }),
                        ]),

                        // —— Spacing ——
                        renderSection("Spacing", false, [
                          el(RangeControl, {
                            label: "Padding Vertical",
                            value: btn.paddingV || 12,
                            min: 0,
                            max: 40,
                            onChange: onChange("paddingV"),
                          }),
                          el(RangeControl, {
                            label: "Padding Horizontal",
                            value: btn.paddingH || 26,
                            min: 0,
                            max: 80,
                            onChange: onChange("paddingH"),
                          }),
                        ]),

                        // —— Shadow ——
                        renderSection("Shadow", false, [
                          el(RangeControl, {
                            label: "Offset X",
                            value: btn.shadowOffsetX,
                            min: -20,
                            max: 20,
                            onChange: onChange("shadowOffsetX"),
                          }),
                          el(RangeControl, {
                            label: "Offset Y",
                            value: btn.shadowOffsetY,
                            min: -20,
                            max: 20,
                            onChange: onChange("shadowOffsetY"),
                          }),
                          el(RangeControl, {
                            label: "Blur",
                            value: btn.shadowBlur,
                            min: 0,
                            max: 20,
                            onChange: onChange("shadowBlur"),
                          }),
                          el("p", {}, "Shadow Color:"),
                          el(ColorPalette, {
                            value: btn.shadowColor,
                            onChange: onChange("shadowColor"),
                          }),
                        ]),
                        el(
                          Button,
                          {
                            isSecondary: true,
                            onClick: () => applyStyleToAll(i),
                            style: { marginTop: "10px" },
                          },
                          "Apply style to all buttons",
                        ),
                      ]);
                    }),
                  ),
                );
              }
              // ---------- Tab 3: Settings ----------
              if (tab.name === "settings") {
                return el(PanelBody, { title: "Settings", initialOpen: true }, [
                  // لكل زر داخل المصفوفة
                  ...buttons.map((btn, i) =>
                    el(
                      PanelBody,
                      {
                        key: `set-${i}`,
                        title: `Button ${i + 1} Settings`,
                        initialOpen: false,
                      },
                      [
                        // 1) سطر اختيار الأيقونة + معاينتها
                        el(
                          PanelRow,
                          { style: { alignItems: "center" } },
                          el(
                            MediaUploadCheck,
                            {},
                            el(MediaUpload, {
                              onSelect: (media) => {
                                props.setAttributes({
                                  buttons: buttons.map((b, idx) =>
                                    idx !== i
                                      ? b
                                      : {
                                          ...b,
                                          iconUrl: media.url,
                                          iconId: media.id,
                                        },
                                  ),
                                });
                              },
                              allowedTypes: ["image"],
                              value: btn.iconId,
                              render: ({ open }) =>
                                el(
                                  Button,
                                  { isSecondary: true, onClick: open },
                                  btn.iconUrl ? "Change Icon" : "Select Icon",
                                ),
                            }),
                          ),
                          // المعاينة إذا وُجد رابط
                          btn.iconUrl &&
                            el("img", {
                              src: btn.iconUrl,
                              alt: "Icon Preview",
                              style: {
                                width: btn.logoSize || 24,
                                height: btn.logoSize || 24,
                                marginLeft: 10,
                                display: "block",
                              },
                            }),
                        ),
                        el(RangeControl, {
                          label: "Logo/Icon Size (px)",
                          value: btn.logoSize || 24,
                          min: 10,
                          max: 80,
                          onChange: (v) => updateButton(i, "logoSize", v),
                        }),
                        // 2) سطر الخيار Open in New Tab
                        el(
                          PanelRow,
                          {},
                          el(ToggleControl, {
                            label: "Open in New Tab",
                            checked: btn.openInNewTab,
                            onChange: (v) => updateButton(i, "openInNewTab", v),
                          }),
                        ),
                      ],
                    ),
                  ),

                  // 3) إعدادات عامة للـ Group
                  el(
                    PanelBody,
                    { title: "Layout & Alignment", initialOpen: false },
                    [
                      el(ToggleControl, {
                        label: "Display buttons vertically",
                        checked: layout === "vertical",
                        onChange: (v) =>
                          props.setAttributes({
                            layout: v ? "vertical" : "horizontal",
                          }),
                      }),
                      el(SelectControl, {
                        label: "Button Alignment",
                        value: alignment,
                        options: [
                          { label: "Left", value: "left" },
                          { label: "Center", value: "center" },
                          { label: "Right", value: "right" },
                        ],
                        onChange: (v) => props.setAttributes({ alignment: v }),
                      }),
                      el(RangeControl, {
                        label: "Horizontal Gap (px)",
                        value: gapHorizontal,
                        min: 0,
                        max: 100,
                        onChange: (v) =>
                          props.setAttributes({ gapHorizontal: v }),
                      }),
                      el(RangeControl, {
                        label: "Vertical Gap (px)",
                        value: gapVertical,
                        min: 0,
                        max: 100,
                        onChange: (v) =>
                          props.setAttributes({ gapVertical: v }),
                      }),
                    ],
                  ),
                ]);
              }
            },
          ),
        ),
        el(ServerSideRender, {
          block: "custom/affiliate-buttons-group",
          attributes: props.attributes,
          key: JSON.stringify(props.attributes),
        }),
      );
    },

    /* Saving remains server-side */
    save: () => null,
  });
})(
  window.wp.blocks,
  window.wp.blockEditor,
  window.wp.element,
  window.wp.components,
);
