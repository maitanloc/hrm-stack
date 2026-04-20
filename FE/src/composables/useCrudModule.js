import { computed, ref, watch } from 'vue';
import { apiRequest } from '@/services/beApi.js';

const defaultListQueryBuilder = ({ page, perPage, search, filters }) => ({
  page,
  per_page: perPage,
  q: search || undefined,
  ...filters,
});

const defaultMapper = (value) => value;

export function useCrudModule(config) {
  const {
    listPath,
    detailPath = (id) => `${listPath}/${id}`,
    createPath = listPath,
    updatePath = (id) => `${listPath}/${id}`,
    deletePath = (id) => `${listPath}/${id}`,
    listQueryBuilder = defaultListQueryBuilder,
    mapItem = defaultMapper,
    mapDetail = defaultMapper,
    toCreatePayload = defaultMapper,
    toUpdatePayload = defaultMapper,
    getItemId = (item) => item?.id,
    initialForm = () => ({}),
    defaultFilters = {},
  } = config;

  const rows = ref([]);
  const loading = ref(false);
  const submitting = ref(false);
  const deletingId = ref(null);
  const errorMessage = ref('');
  const successMessage = ref('');

  const page = ref(1);
  const perPage = ref(10);
  const total = ref(0);
  const lastPage = ref(1);

  const search = ref('');
  const filters = ref({ ...defaultFilters });

  const selectedId = ref(null);
  const selectedDetail = ref(null);
  const detailLoading = ref(false);

  const formMode = ref('create');
  const formOpen = ref(false);
  const formData = ref(initialForm());

  const canPrev = computed(() => page.value > 1);
  const canNext = computed(() => page.value < lastPage.value);

  const clearMessages = () => {
    errorMessage.value = '';
    successMessage.value = '';
  };

  const fetchList = async () => {
    clearMessages();
    loading.value = true;
    try {
      const query = listQueryBuilder({
        page: page.value,
        perPage: perPage.value,
        search: search.value,
        filters: filters.value,
      });
      const payload = await apiRequest(listPath, { query });
      const data = Array.isArray(payload?.data) ? payload.data : [];
      rows.value = data.map(mapItem);

      const meta = payload?.meta || {};
      total.value = Number(meta.total || data.length || 0);
      lastPage.value = Number(meta.last_page || 1);
      if (lastPage.value < 1) lastPage.value = 1;
      if (page.value > lastPage.value) {
        page.value = lastPage.value;
      }
    } catch (error) {
      errorMessage.value = error?.message || 'Khong tai duoc danh sach';
      rows.value = [];
      total.value = 0;
      lastPage.value = 1;
    } finally {
      loading.value = false;
    }
  };

  const fetchDetail = async (id) => {
    if (!id) return;
    clearMessages();
    detailLoading.value = true;
    selectedId.value = id;
    try {
      const payload = await apiRequest(detailPath(id));
      selectedDetail.value = mapDetail(payload?.data || null);
    } catch (error) {
      selectedDetail.value = null;
      errorMessage.value = error?.message || 'Khong tai duoc chi tiet';
    } finally {
      detailLoading.value = false;
    }
  };

  const selectRow = async (row) => {
    const id = getItemId(row);
    if (!id) return;
    await fetchDetail(id);
  };

  const openCreate = () => {
    clearMessages();
    formMode.value = 'create';
    formData.value = initialForm();
    formOpen.value = true;
  };

  const openEdit = (row) => {
    clearMessages();
    formMode.value = 'edit';
    formData.value = { ...row };
    formOpen.value = true;
  };

  const closeForm = () => {
    formOpen.value = false;
  };

  const submitForm = async () => {
    clearMessages();
    submitting.value = true;
    try {
      if (formMode.value === 'create') {
        await apiRequest(createPath, {
          method: 'POST',
          body: toCreatePayload(formData.value),
        });
        successMessage.value = 'Da tao moi thanh cong';
      } else {
        const id = getItemId(formData.value);
        if (!id) throw new Error('Ban ghi khong hop le');
        await apiRequest(updatePath(id), {
          method: 'PATCH',
          body: toUpdatePayload(formData.value),
        });
        successMessage.value = 'Da cap nhat thanh cong';
      }
      closeForm();
      await fetchList();
      if (selectedId.value) {
        await fetchDetail(selectedId.value);
      }
    } catch (error) {
      errorMessage.value = error?.message || 'Khong luu duoc du lieu';
    } finally {
      submitting.value = false;
    }
  };

  const removeRow = async (row) => {
    clearMessages();
    const id = getItemId(row);
    if (!id) return;
    deletingId.value = id;
    try {
      await apiRequest(deletePath(id), { method: 'DELETE' });
      successMessage.value = 'Da xoa thanh cong';
      if (selectedId.value === id) {
        selectedId.value = null;
        selectedDetail.value = null;
      }
      await fetchList();
    } catch (error) {
      errorMessage.value = error?.message || 'Khong xoa duoc du lieu';
    } finally {
      deletingId.value = null;
    }
  };

  const goPrev = async () => {
    if (!canPrev.value) return;
    page.value -= 1;
    await fetchList();
  };

  const goNext = async () => {
    if (!canNext.value) return;
    page.value += 1;
    await fetchList();
  };

  const applySearch = async (value) => {
    search.value = value;
    page.value = 1;
    await fetchList();
  };

  const setFilter = async (key, value) => {
    filters.value = {
      ...filters.value,
      [key]: value,
    };
    page.value = 1;
    await fetchList();
  };

  watch(perPage, async () => {
    page.value = 1;
    await fetchList();
  });

  return {
    rows,
    loading,
    submitting,
    deletingId,
    errorMessage,
    successMessage,
    page,
    perPage,
    total,
    lastPage,
    search,
    filters,
    selectedId,
    selectedDetail,
    detailLoading,
    formMode,
    formOpen,
    formData,
    canPrev,
    canNext,
    fetchList,
    fetchDetail,
    selectRow,
    openCreate,
    openEdit,
    closeForm,
    submitForm,
    removeRow,
    goPrev,
    goNext,
    applySearch,
    setFilter,
    clearMessages,
  };
}
