
const formatDate = (
    value:string,
    {
        locale = 'pl-PL',
        dateStyle = 'long',
        timeStyle = 'short',
        timeZone = undefined,
    } = {}
) => {
    if (!value) return ''

    const date = new Date(value)
    if (isNaN(date.getTime())) return ''

    return new Intl.DateTimeFormat(locale, {
        dateStyle,
        timeStyle,
        timeZone,
    }).format(date)
}

export { formatDate }