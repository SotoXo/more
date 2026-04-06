import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md shadow-sm transition-all duration-300 ' +
                'bg-[#0d0d12] border-[rgba(255,255,255,0.1)] text-[#e2e8f0] ' +
                'focus:border-[#818cf8] focus:ring-[#818cf8] focus:ring-opacity-50 ' +
                className
            }
            ref={localRef}
        />
    );
});
