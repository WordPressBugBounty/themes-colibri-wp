(function ($) {
  async function sleep(time) {
    return new Promise(resolve => setTimeout(resolve, time));
  }
  async function saveCustomizerSettings() {
    let promiseResolve;
    const promise = new Promise((resolve, reject) => {
      promiseResolve = resolve;
    });
    let doneCallback = () => {
      promiseResolve();
    };
    try {
      if (!_.isEmpty(wp.customize.dirtyValues())) {
        let executeCallback = true;
        wp.customize.bind('save', () => {
          if (executeCallback) {
            $(window).off('beforeunload');
            setTimeout(doneCallback, 2000);
            executeCallback = false;
          }
        });
        wp.customize.previewer.save();
      } else {
        $(window).off('beforeunload');
        setTimeout(doneCallback, 500);
      }
    } catch (e) {
      doneCallback();
      console.error(e);
    }
    await promise;
  }
  function getSiteLeadsBackendData(path, defaultValue) {
    return _.get(top.colibriwpSiteLeadsCustomizerData, path, defaultValue);
  }
  function getTranslatedText(name) {
    return getSiteLeadsBackendData(['translations', name], '');
  }
  function getWithThemePrefix(path) {
    let themePrefix = getSiteLeadsBackendData('themePrefix');
    return themePrefix + path;
  }
  const PLUGIN_STATUSES = {
    NOT_INSTALLED: 'not-installed',
    INSTALLED: 'installed',
    ACTIVE: 'active'
  };
  const contactSectionId = getSiteLeadsBackendData('contactSectionId');
  const phoneNumberWpSettingId = getWithThemePrefix('siteleads_number');
  const showContactPhoneWpSettingId = getWithThemePrefix('show_contact_phone');
  const sectionName = getWithThemePrefix('contact_settings');
  let buttonNode = null;
  let phoneInputNode = null;
  let requestIsPending = false;
  let pluginSettingUrl = getSiteLeadsBackendData('pluginSettingsUrl', '#');
  let isLoading = false;
  const setIsLoadingText = text => {
    const infoNoticeNode = document.querySelector('.colibriwp-siteleads-integration-button__notice--info');
    if (!infoNoticeNode) {
      return;
    }
    infoNoticeNode.style.display = 'flex';
    const spinner = document.createElement('span');
    spinner.classList.add('spinner');
    spinner.classList.add('is-active');
    const textSpan = document.createElement('span');
    textSpan.innerHTML = text;
    infoNoticeNode.innerHTML = '';
    infoNoticeNode.appendChild(spinner);
    infoNoticeNode.appendChild(textSpan);
  };
  const hideInfoNotice = () => {
    const infoNoticeNode = document.querySelector('.colibriwp-siteleads-integration-button__notice--info');
    if (!infoNoticeNode) {
      return;
    }
    infoNoticeNode.style.display = 'none';
  };
  const setIsLoading = newValue => {
    const buttonNode = document.querySelector('.colibriwp-siteleads-integration-button');
    if (!buttonNode) {
      return;
    }
    if (newValue) {
      buttonNode.classList.add('colibriwp-loading');
    } else {
      buttonNode.classList.remove('colibriwp-loading');
      hideInfoNotice();
    }
  };
  let errorMessage = false;
  const setErrorMessage = newValue => {
    errorMessage = newValue;
    unUpdateNoticeErrorText();
  };
  let currentStatus = getSiteLeadsBackendData('pluginStatus');
  function setCurrentStatus(newValue) {
    currentStatus = newValue;
    updateControlReactiveData();
  }
  function getButtonLabel() {
    switch (currentStatus) {
      case PLUGIN_STATUSES.ACTIVE:
        return getTranslatedText('install_button_text_active_plugin');
      case PLUGIN_STATUSES.INSTALLED:
        return getTranslatedText('install_button_text_installed_plugin');
      case PLUGIN_STATUSES.NOT_INSTALLED:
        return getTranslatedText('install_button_not_installed_plugin');
      default:
        return 'Error';
    }
  }
  function getNoticeTitle() {
    switch (currentStatus) {
      case PLUGIN_STATUSES.ACTIVE:
        return getTranslatedText('notice_title_active');
      case PLUGIN_STATUSES.INSTALLED:
        return getTranslatedText('notice_title_installed');
      case PLUGIN_STATUSES.NOT_INSTALLED:
        return getTranslatedText('notice_title_not_installed');
      default:
        return 'Error';
    }
  }
  function getNoticeError() {
    if (!errorMessage) {
      return false;
    }
    return 'Error: ' + errorMessage;
  }
  function onUpdateButtonTextAndListeners() {
    buttonNode = document.querySelector('.colibriwp-siteleads-integration-button');
    if (!buttonNode) {
      return;
    }
    buttonNode.innerHTML = getButtonLabel();
    buttonNode.removeEventListener('click', onHandleButtonClick);
    buttonNode.addEventListener('click', onHandleButtonClick);
  }
  function onUpdateNoticeText() {
    let noticeNodeContainer = document.querySelector('.colibriwp-siteleads-integration-button__container');
    if (!noticeNodeContainer) {
      return;
    }
    let titleNode = noticeNodeContainer.querySelector('.colibriwp-siteleads-integration-button__title');
    titleNode.innerHTML = getNoticeTitle();
    const titleSiteLeadsSpan = document.createElement('span');
    titleSiteLeadsSpan.innerHTML = 'SiteLeads';
    titleNode.innerHTML += '&nbsp;' + titleSiteLeadsSpan.outerHTML;
    titleNode.innerHTML += '&nbsp;' + getTranslatedText('plugin_text');
    let descriptionNode1 = noticeNodeContainer.querySelector('.colibriwp-siteleads-integration-button__description_1');
    descriptionNode1.innerHTML = getTranslatedText('notice_description_1');
    let descriptionNode2 = noticeNodeContainer.querySelector('.colibriwp-siteleads-integration-button__description_2');
    descriptionNode2.innerHTML = getTranslatedText('notice_description_2');
    let descriptionNode3 = noticeNodeContainer.querySelector('.colibriwp-siteleads-integration-button__description_3');
    descriptionNode3.innerHTML = getTranslatedText('notice_description_3');
  }
  function unUpdateNoticeErrorText() {
    hideInfoNotice();
    let noticeErrorNode = document.querySelector('.colibriwp-siteleads-integration-button__notice--error');
    if (!noticeErrorNode) {
      return;
    }
    let error = getNoticeError();
    if (error) {
      noticeErrorNode.innerHTML = error;
      noticeErrorNode.style.display = 'block';
    } else {
      noticeErrorNode.style.display = 'none';
    }
  }
  async function toggleEnabledSiteLeadsPlugin(newValue) {
    const ajaxHandle = getSiteLeadsBackendData('siteLeadsToggleEnabledWpAjaxHandle');
    const nonce = getSiteLeadsBackendData('siteLeadsNonce');
    const widgetId = getWidgetId();
    const promise = new Promise((resolve, reject) => {
      wp.ajax.post(ajaxHandle, {
        enabled: newValue ? '1' : '0',
        widget_id: widgetId,
        _wpnonce: nonce
      }).done(response => {
        resolve(response);
      }).fail(error => {
        reject(error);
      });
    });
    try {
      const result = await promise;
      wp.customize.previewer.refresh();
      return true;
    } catch (e) {
      //setErrorMessage(getTranslatedText('error_could_not_toggle_plugin_enabled'));
      console.error(e);
      return false;
    }
  }
  ;
  function setIsEnabled(newValue) {
    if (currentStatus === PLUGIN_STATUSES.ACTIVE) {
      toggleEnabledSiteLeadsPlugin(newValue);
    } else {
      wp.customize.previewer.refresh();
    }
  }
  ;
  function onUpdatePhoneInput() {
    phoneInputNode = document.querySelector('#_customize-input-' + getWithThemePrefix('siteleads_number'));
    if (!phoneInputNode) {
      return;
    }
    if (currentStatus === PLUGIN_STATUSES.ACTIVE) {
      phoneInputNode.setAttribute('disabled', 'disabled');
      return;
    }
    phoneInputNode.removeAttribute('disabled');
  }
  function onAddEnabledToggleListener() {
    wp.customize(showContactPhoneWpSettingId, function (setting) {
      setting.unbind(setIsEnabled);
      setting.bind(setIsEnabled);
    });
  }
  function updateControlReactiveData() {
    onUpdateButtonTextAndListeners();
    onUpdateNoticeText();
    onUpdatePhoneInput();
    onAddEnabledToggleListener();
  }
  function onEnableWordpressSetting() {
    try {
      const widgetCustomizerSettingEnabled = wp.customize(showContactPhoneWpSettingId).get();
      if (!widgetCustomizerSettingEnabled) {
        wp.customize(showContactPhoneWpSettingId).set(true);
      }
    } catch (e) {
      console.error(e);
    }
  }
  const onInstallSiteLeadsPlugin = async () => {
    const slug = getSiteLeadsBackendData('pluginSlug');
    const promise = new Promise((resolve, reject) => {
      wp.updates.ajax("install-plugin", {
        slug: slug,
        success: () => {
          resolve();
        },
        error: e => {
          if ('folder_exists' === e.errorCode) {
            resolve();
          } else {
            reject();
          }
        }
      });
    });
    try {
      setIsLoadingText(getTranslatedText('info_notice_installing'));
      const result = await promise;
      setCurrentStatus(PLUGIN_STATUSES.INSTALLED);
      return true;
    } catch (e) {
      setErrorMessage(getTranslatedText('error_could_not_install_plugin'));
      console.error(e);
      return false;
    }
  };
  const onActivateSiteLeads = async () => {
    const activationUrl = getSiteLeadsBackendData('activationLink');
    let promise = new Promise(async (resolve, reject) => {
      try {
        let result = await fetch(activationUrl);
        if (!result?.ok) {
          reject(result?.statusText);
        }
        resolve();
      } catch (e) {
        reject(e);
      }
    });
    try {
      setIsLoadingText(getTranslatedText('info_notice_activating'));
      const result = await promise;
      setCurrentStatus(PLUGIN_STATUSES.ACTIVE);

      // onEnableWordpressSetting();
      await saveCustomizerSettings();
      await initSetupForSiteLeadsPlugin();
      wp.customize.previewer.save();
      return true;
    } catch (e) {
      setErrorMessage(getTranslatedText('error_could_not_activate_plugin'));
      console.error(e);
      return false;
    }
  };
  function getWpValue(path) {
    try {
      return top.wp.customize?.(path)?.get?.() || null;
    } catch (e) {
      console.error(e);
    }
  }
  const initSetupForSiteLeadsPlugin = async () => {
    const ajaxHandle = getSiteLeadsBackendData('siteLeadsInitWpAjaxHandle');
    const phoneNumber = getWpValue(phoneNumberWpSettingId) || '';
    const nonce = getSiteLeadsBackendData('siteLeadsNonce');
    const promise = new Promise((resolve, reject) => {
      wp.ajax.post(ajaxHandle, {
        phone: phoneNumber,
        _wpnonce: nonce,
        'start_source': 'contact-panel'
      }).done(response => {
        resolve(response);
      }).fail(error => {
        reject(error);
      });
    });
    try {
      setIsLoadingText(getTranslatedText('info_notice_init'));
      const result = await promise;
      wp.customize.previewer.refresh();
      return result;
    } catch (e) {
      setErrorMessage(getTranslatedText('error_could_not_init_plugin_data'));
      console.error(e);
      return false;
    }
  };
  const getWidgetId = () => {
    const iframe = document.querySelector('#customize-preview iframe');
    if (!iframe) {
      return null;
    }
    const iframeDocument = iframe.contentDocument;
    const widget = iframeDocument?.querySelector?.('[data-widget-id]');
    if (!widget) {
      return null;
    }
    ;
    const widgetId = widget.getAttribute('data-widget-id');
    return widgetId;
  };
  const getPluginSettingsUrlBasedOnWidgetIdInPreview = () => {
    const widgetId = getWidgetId();
    if (!widgetId) {
      return pluginSettingUrl;
    }
    const adminUrl = getSiteLeadsBackendData('adminPhpUrl');
    try {
      let widgetSettingsUrl = new URL(adminUrl);
      let searchParams = widgetSettingsUrl.searchParams;
      searchParams.append('page', 'siteleads');
      searchParams.append('inner_page', 'channel-selection');
      searchParams.append('widgetId', widgetId);
      searchParams.append('action', 'edit');
      return widgetSettingsUrl.toString();
    } catch (e) {
      console.error(e);
      return pluginSettingUrl;
    }
  };
  function onRedirectToManageOptions() {
    let href = getPluginSettingsUrlBasedOnWidgetIdInPreview();
    if (!href) {
      return;
    }
    window.open(href, "_blank");
  }
  async function onHandleButtonClick() {
    if (currentStatus === PLUGIN_STATUSES.ACTIVE) {
      onRedirectToManageOptions();
      return;
    }
    if (requestIsPending) {
      return;
    }
    errorMessage = null;
    unUpdateNoticeErrorText();
    requestIsPending = true;
    setIsLoading(true);
    switch (currentStatus) {
      case PLUGIN_STATUSES.NOT_INSTALLED:
        await installAndActivateSiteLeads();
        break;
      case PLUGIN_STATUSES.INSTALLED:
        await onActivateSiteLeads();
        break;
    }
    requestIsPending = false;
    setIsLoading(false);
  }
  ;
  const installAndActivateSiteLeads = async () => {
    try {
      const installResponse = await onInstallSiteLeadsPlugin();
      if (!installResponse) {
        return false;
      }
      const activateResponse = await onActivateSiteLeads();
      if (!activateResponse) {
        return false;
      }
      return true;
    } catch (e) {
      return false;
      // removed by dead control flow

    }
  };
  function addButtonEventListeners() {
    updateControlReactiveData();
  }

  //Siteleads init
  function initApp() {
    let container = document.querySelector('.colibriwp-siteleads-integration-button__container');
    if (!container) {
      return;
    }
    let isLoaded = container.getAttribute('data-loaded', false);
    if (isLoaded === 'true') {
      return;
    }
    container.setAttribute('data-loaded', 'true');
    addButtonEventListeners();

    // Reveal the container now that everything is ready
    container.style.display = 'flex';
  }
  function onAddPanelReflowListener() {
    const parentWP = parent.wp;
    const onUpdate = () => {
      // Check if our specific section is currently active/expanded
      if (parentWP.customize.section(sectionName).expanded()) {
        initApp();
      }
    };
    parentWP.customize.bind('pane-contents-reflowed', onUpdate);
    parentWP.customize?.bind('save', onUpdate);
    setTimeout(() => {
      initApp();
    }, 0);
  }
  async function closeOtherSections() {
    //close colibri theme sections
    try {
      wp.customize.section.each(function (section) {
        if (section?.id === contactSectionId) {
          return;
        }
        if (section.expanded()) {
          if (section?.params?.type === 'colibri_section') {
            section?._toggleExpanded?.(false);
          }
        }
      });
    } catch (e) {
      console.error(e);
    }

    //close colibri plugin sections
    try {
      top?.hop?.sidebar?.$store?.dispatch?.('page/SET_SELECTED_NODE', -1);
      if (top?.hop?.sidebar) {
        await sleep(100);
      }
    } catch (e) {
      console.error(e);
    }
  }
  async function openContactSettings() {
    await closeOtherSections();
    wp?.customize?.section?.(contactSectionId, function (section) {
      section.expand();
    });
  }
  function onAddClickOnWidgetOpenPanel(e) {
    let target = e?.target;
    if (!target?.closest?.('.siteleads-fc-widgets')) {
      return;
    }
    openContactSettings();
  }
  async function addPreviewerListeners() {
    await sleep(100);
    let iframe = document.querySelector('#customize-preview iframe');
    if (!iframe) {
      return;
    }
    let iframeDocument = iframe?.contentWindow?.document;
    if (!iframeDocument) {
      return;
    }
    iframeDocument.removeEventListener('click', onAddClickOnWidgetOpenPanel, true);
    iframeDocument.addEventListener('click', onAddClickOnWidgetOpenPanel, true);
  }
  $(document).ready(function () {
    onAddPanelReflowListener();
    wp.customize?.previewer?.bind?.('ready', addPreviewerListeners);
  });
})(jQuery);
