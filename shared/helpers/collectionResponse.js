const normalizeCollectionResponse = (response) => {
  const data = response ?? {}

  const items = data.member
    ?? data['hydra:member']
    ?? (Array.isArray(data) ? data : [])

  const totalRecords = data.totalItems
    ?? data['hydra:totalItems']
    ?? (Array.isArray(items) ? items.length : 0)

  if (items && typeof items === 'object') {
    items.totalRecords = totalRecords
    items.totalItems = totalRecords
  }

  return {
    items,
    totalRecords,
    member: items,
    totalItems: totalRecords,
    raw: data
  }
}

export { normalizeCollectionResponse }
