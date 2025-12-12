export function toLocalISO(date: Date) {
    const d = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
    return d.toISOString().split('T')[0]
}