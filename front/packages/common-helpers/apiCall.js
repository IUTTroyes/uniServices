import { showSuccess, showDanger } from '@helpers/toast.js'

const apiCall = async (serviceMethod, args = [], successMessage = 'Operation Successful', errorMessage = 'An error occurred', showToast = true) => {
  try {
    const response = await serviceMethod(...args)
    if (response.status === 200 || response.status === 201 || response.status === 204) {
      if (showToast) showSuccess(successMessage)
    } else {
      if (showToast) showDanger(errorMessage)
    }
    return response.data
  } catch (error) {
    if (showToast) showDanger(error.response?.data?.message || error.message)
    throw error
  }
}

export default apiCall
