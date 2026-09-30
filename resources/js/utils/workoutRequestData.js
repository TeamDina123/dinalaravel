// Keep exercise groups as JSON so multipart uploads preserve nulls and nested arrays.
export function workoutRequestData(data, coverImage) {
    if (!coverImage) return data;

    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => {
        if (value === undefined) return;
        formData.append(key, value !== null && typeof value === 'object'
            ? JSON.stringify(value)
            : value ?? '');
    });
    formData.append('cover_image', coverImage);
    return formData;
}
